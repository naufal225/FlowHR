@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-office-location-form]');
            if (!root) return;

            const config = JSON.parse(root.dataset.config);
            const els = {
                map: root.querySelector('#office-location-map'),
                searchInput: root.querySelector('#office_place_search'),
                searchStatus: root.querySelector('[data-office-location-search-status]'),
                resultsPanel: root.querySelector('[data-office-location-results-panel]'),
                resultsList: root.querySelector('[data-office-location-results-list]'),
                resultsMeta: root.querySelector('[data-office-location-results-meta]'),
                resultsCount: root.querySelector('[data-office-location-results-count]'),
                resultsEmpty: root.querySelector('[data-office-location-results-empty]'),
                selectedPanel: root.querySelector('[data-office-location-selected-panel]'),
                selectedTitle: root.querySelector('[data-office-location-selected-title]'),
                selectedAddress: root.querySelector('[data-office-location-selected-address]'),
                mapStatus: root.querySelector('[data-office-location-map-status]'),
                latitude: root.querySelector('#latitude'),
                longitude: root.querySelector('#longitude'),
                latitudeDisplay: root.querySelector('[data-office-location-latitude-display]'),
                longitudeDisplay: root.querySelector('[data-office-location-longitude-display]'),
                radiusInput: root.querySelector('#radius_meter'),
                radiusRange: root.querySelector('[data-office-location-radius-range]'),
                radiusDisplay: root.querySelector('[data-office-location-radius-display]'),
                radiusChip: root.querySelector('[data-office-location-radius-chip]'),
                radiusHelper: root.querySelector('[data-office-location-radius-helper]'),
                radiusPresets: root.querySelectorAll('[data-office-location-radius-preset]'),
                address: root.querySelector('#address'),
                timezone: root.querySelector('#timezone'),
                timezoneDisplay: root.querySelector('[data-office-location-timezone-display]'),
                timezoneStatus: root.querySelector('[data-office-location-timezone-status]'),
                timezoneRefresh: root.querySelector('[data-office-location-timezone-refresh]'),
            };

            const palette = {
                info: 'border-sky-200 bg-sky-50 text-sky-700',
                success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                warning: 'border-amber-200 bg-amber-50 text-amber-700',
                error: 'border-rose-200 bg-rose-50 text-rose-700',
            };

            const state = {
                map: null,
                marker: null,
                circle: null,
                pendingTimezoneRequest: 0,
                coordinateDebounce: null,
                mapRenderWatchdog: null,
                searchDebounce: null,
                latestSearchRequestId: 0,
                candidatePredictions: [],
                selectedPredictionPlaceId: null,
                AutocompleteSuggestion: null,
                AutocompleteSessionToken: null,
                searchSessionToken: null,
                initialLatitude: numericOrNull(config.initialState.latitude),
                initialLongitude: numericOrNull(config.initialState.longitude),
                defaultPosition: {
                    lat: Number(config.defaultCenter.lat),
                    lng: Number(config.defaultCenter.lng),
                },
            };

            bindRadiusInputs();
            bindCoordinateInputs();
            bindTimezoneInput();
            bindSearchInput();
            initializeGoogleMapsExperience();
            updateSnapshot();
            updateRadiusPresentation(getRadiusValue());
            renderSearchIdleState();

            if (els.address.value.trim() !== '' && hasValidCoordinates(getLatitudeValue(), getLongitudeValue())) {
                renderSelectedCandidate('Current mapped location', els.address.value.trim());
            }

            if (hasValidCoordinates(getLatitudeValue(), getLongitudeValue())) {
                requestTimezoneResolution('Loaded the existing office coordinates.', { preserveTimezoneOnFailure: true });
            } else if (els.timezone.value.trim() !== '') {
                renderStatus(els.timezoneStatus, 'info', 'Existing timezone loaded. Adjust the map point to refresh it automatically.');
            } else {
                renderStatus(els.timezoneStatus, 'info', 'Select a point on the map, then timezone detection can run automatically.');
            }

            function renderSearchIdleState() {
                renderStatus(els.searchStatus, 'info', 'Type an office name, business name, or address to see multiple candidate locations.');
            }

            async function initializeGoogleMapsExperience() {
                els.searchInput.disabled = true;
                els.searchInput.placeholder = 'Loading Google Maps search...';

                if (!config.googleMapsBrowserKey) {
                    renderStatus(els.mapStatus, 'error', 'Google Maps is not configured. You can still enter coordinates manually and save the office.');
                    renderStatus(els.searchStatus, 'warning', 'Search is unavailable until GOOGLE_MAPS_BROWSER_KEY is configured.');
                    hideResultsPanel();
                    return;
                }

                renderStatus(els.mapStatus, 'info', 'Loading Google Maps...');
                renderStatus(els.searchStatus, 'info', 'Loading Google Places search...');
                registerGoogleMapsAuthFailureHandler();

                try {
                    await loadGoogleMapsApi(config.googleMapsBrowserKey);

                    const [{ Map, Circle }, { AutocompleteSuggestion, AutocompleteSessionToken }] = await Promise.all([
                        window.google.maps.importLibrary('maps'),
                        window.google.maps.importLibrary('places'),
                    ]);

                    state.AutocompleteSuggestion = AutocompleteSuggestion;
                    state.AutocompleteSessionToken = AutocompleteSessionToken;
                    refreshSearchSessionToken();

                    initializeGoogleMap(Map, Circle);
                    els.searchInput.disabled = false;
                    els.searchInput.placeholder = 'Search the office address, office name, or business name';
                    renderSearchIdleState();
                } catch (error) {
                    els.searchInput.disabled = true;
                    renderStatus(els.mapStatus, 'error', 'Google Maps failed to load. You can still enter coordinates manually.');
                    renderStatus(els.searchStatus, 'warning', 'Google Places search is unavailable because the browser loader did not complete successfully.');
                    hideResultsPanel();
                }
            }

            function registerGoogleMapsAuthFailureHandler() {
                window.gm_authFailure = () => {
                    renderStatus(els.mapStatus, 'error', 'Google Maps rejected the browser key. Check referrer restrictions and Maps JavaScript API access.');
                    renderStatus(els.searchStatus, 'error', 'Google Places search is unavailable because the browser key was rejected.');
                    hideResultsPanel();
                };
            }

            function loadGoogleMapsApi(browserKey) {
                if (window.google?.maps?.importLibrary) {
                    return Promise.resolve(window.google.maps);
                }

                if (window.flowHrOfficeLocationGoogleMapsPromise) {
                    return window.flowHrOfficeLocationGoogleMapsPromise;
                }

                window.flowHrOfficeLocationGoogleMapsPromise = new Promise((resolve, reject) => {
                    const existingScript = document.querySelector('script[data-google-maps-office-loader="true"]');

                    const verifyLoaded = (attempt = 0) => {
                        if (window.google?.maps?.importLibrary) {
                            resolve(window.google.maps);
                            return;
                        }

                        if (attempt >= 20) {
                            reject(new Error('Google Maps API loaded without importLibrary support.'));
                            return;
                        }

                        window.setTimeout(() => verifyLoaded(attempt + 1), 100);
                    };

                    if (existingScript) {
                        if (existingScript.dataset.loaded === 'true') {
                            verifyLoaded();
                            return;
                        }

                        existingScript.addEventListener('load', () => {
                            existingScript.dataset.loaded = 'true';
                            verifyLoaded();
                        }, { once: true });
                        existingScript.addEventListener('error', () => reject(new Error('Google Maps API failed to load.')), { once: true });
                        return;
                    }

                    const script = document.createElement('script');
                    const query = new URLSearchParams({
                        key: browserKey,
                        v: 'weekly',
                        loading: 'async',
                        libraries: 'places',
                    });

                    script.src = `https://maps.googleapis.com/maps/api/js?${query.toString()}`;
                    script.async = true;
                    script.defer = true;
                    script.dataset.googleMapsOfficeLoader = 'true';
                    script.addEventListener('load', () => {
                        script.dataset.loaded = 'true';
                        verifyLoaded();
                    }, { once: true });
                    script.addEventListener('error', () => reject(new Error('Google Maps API failed to load.')), { once: true });
                    document.head.appendChild(script);
                }).catch((error) => {
                    window.flowHrOfficeLocationGoogleMapsPromise = null;
                    throw error;
                });

                return window.flowHrOfficeLocationGoogleMapsPromise;
            }

            function initializeGoogleMap(Map, Circle) {
                const hasSavedLocation = hasValidCoordinates(state.initialLatitude, state.initialLongitude);
                const startPosition = hasSavedLocation
                    ? { lat: state.initialLatitude, lng: state.initialLongitude }
                    : state.defaultPosition;
                const startZoom = hasSavedLocation ? 16 : Number(config.defaultCenter.zoom || 13);

                state.map = new Map(els.map, {
                    center: startPosition,
                    zoom: startZoom,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                    clickableIcons: false,
                    gestureHandling: 'greedy',
                });

                state.marker = new window.google.maps.Marker({
                    map: state.map,
                    position: startPosition,
                    draggable: true,
                    title: 'Office location pin',
                });

                if (!hasSavedLocation) {
                    state.marker.setOpacity(0.78);
                }

                state.circle = new Circle({
                    map: state.map,
                    center: startPosition,
                    radius: getRadiusValue(),
                    strokeColor: '#2563eb',
                    strokeOpacity: 0.92,
                    strokeWeight: 2,
                    fillColor: '#60a5fa',
                    fillOpacity: 0.18,
                    clickable: false,
                });

                window.google.maps.event.addListenerOnce(state.map, 'tilesloaded', () => {
                    window.clearTimeout(state.mapRenderWatchdog);
                    renderStatus(
                        els.mapStatus,
                        hasSavedLocation ? 'success' : 'info',
                        hasSavedLocation
                            ? 'Google Maps is ready. Drag the pin or click the map to refine the saved office point.'
                            : 'Google Maps is ready. Search a place, compare the candidate locations, then choose the correct office point.'
                    );
                });

                state.mapRenderWatchdog = window.setTimeout(() => {
                    renderStatus(els.mapStatus, 'warning', 'Google Maps loaded but the base map tiles did not render. Check browser-key referrer restrictions, billing, and Maps JavaScript API access.');
                }, 4000);

                state.marker.addListener('dragend', (event) => {
                    if (!event.latLng) return;

                    commitLocation(event.latLng.lat(), event.latLng.lng(), 'Pin moved manually.');
                    renderStatus(els.mapStatus, 'success', 'Pin updated. The saved coordinates and timezone are refreshing now.');
                });

                state.map.addListener('click', (event) => {
                    if (!event.latLng) return;

                    commitLocation(event.latLng.lat(), event.latLng.lng(), 'Map point selected.');
                    renderStatus(els.mapStatus, 'success', 'Location updated from Google Maps. Drag the pin further if you need a more exact point.');
                });

                stabilizeMapRendering(startPosition);
                renderStatus(els.mapStatus, 'info', 'Google Maps loaded. Rendering the base map...');
            }

            function bindSearchInput() {
                els.searchInput.addEventListener('input', () => {
                    const query = els.searchInput.value.trim();
                    window.clearTimeout(state.searchDebounce);

                    if (query === '') {
                        state.latestSearchRequestId += 1;
                        state.candidatePredictions = [];
                        clearResultsList();
                        hideResultsPanel();
                        renderSearchIdleState();
                        return;
                    }

                    if (!state.AutocompleteSuggestion) {
                        renderStatus(els.searchStatus, 'warning', 'Google Places search is still loading. Wait a moment, then try again.');
                        return;
                    }

                    if (query.length < 2) {
                        state.candidatePredictions = [];
                        clearResultsList();
                        hideResultsPanel();
                        renderStatus(els.searchStatus, 'info', 'Type at least 2 characters to search for multiple candidate locations.');
                        return;
                    }

                    state.searchDebounce = window.setTimeout(() => {
                        fetchCandidatePlaces(query);
                    }, 280);
                });

                els.searchInput.addEventListener('focus', () => {
                    if (state.candidatePredictions.length > 0 && els.searchInput.value.trim().length >= 2) {
                        showResultsPanel();
                    }
                });

                els.searchInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        hideResultsPanel();
                        return;
                    }

                    if (event.key !== 'Enter') {
                        return;
                    }

                    event.preventDefault();
                    const query = els.searchInput.value.trim();

                    if (query.length < 2) {
                        renderStatus(els.searchStatus, 'info', 'Type at least 2 characters to search for candidate locations.');
                        return;
                    }

                    if (state.candidatePredictions.length > 0 && !resultsPanelIsHidden()) {
                        renderStatus(els.searchStatus, 'info', 'Choose one of the candidate locations below before the map updates.');
                        return;
                    }

                    fetchCandidatePlaces(query);
                });
            }

            function refreshSearchSessionToken() {
                if (!state.AutocompleteSessionToken) {
                    state.searchSessionToken = null;
                    return;
                }

                state.searchSessionToken = new state.AutocompleteSessionToken();
            }

            function ensureSearchSessionToken() {
                if (!state.searchSessionToken) {
                    refreshSearchSessionToken();
                }

                return state.searchSessionToken;
            }

            async function fetchCandidatePlaces(query) {
                if (!state.AutocompleteSuggestion) {
                    renderStatus(els.searchStatus, 'warning', 'Google Places search is still loading. Wait a moment, then try again.');
                    return;
                }

                const requestId = ++state.latestSearchRequestId;
                renderSearchLoading(query);

                try {
                    const { suggestions, fallbackUsed } = await requestAutocompleteSuggestions(query);

                    if (requestId !== state.latestSearchRequestId) {
                        return;
                    }

                    state.candidatePredictions = suggestions
                        .map((suggestion) => suggestion.placePrediction)
                        .filter(Boolean);

                    renderCandidateResults(query, state.candidatePredictions);

                    if (fallbackUsed && state.candidatePredictions.length > 0) {
                        renderStatus(els.searchStatus, 'warning', 'Google Maps returned results after retrying with a wider search. Choose the correct location below.');
                    }
                } catch (error) {
                    if (requestId !== state.latestSearchRequestId) {
                        return;
                    }

                    console.error('FlowHR office location autocomplete failed.', error);
                    state.candidatePredictions = [];
                    renderSearchError(query, error);
                }
            }

            function buildAutocompleteRequests(query) {
                const baseRequest = {
                    input: query,
                    sessionToken: ensureSearchSessionToken(),
                };

                const origin = getAutocompleteOrigin();
                const locationBias = buildAutocompleteBiasFromMap();

                return [
                    { ...baseRequest, origin, locationBias },
                    { ...baseRequest, origin },
                    baseRequest,
                ];
            }

            async function requestAutocompleteSuggestions(query) {
                const requests = buildAutocompleteRequests(query);
                let lastError = null;

                for (let index = 0; index < requests.length; index += 1) {
                    try {
                        const result = await state.AutocompleteSuggestion.fetchAutocompleteSuggestions(requests[index]);

                        return {
                            suggestions: result?.suggestions ?? [],
                            fallbackUsed: index > 0,
                        };
                    } catch (error) {
                        lastError = error;
                    }
                }

                throw lastError || new Error('Google Maps did not return autocomplete suggestions.');
            }

            async function handleCandidateSelection(prediction) {
                const candidateTitle = getPredictionPrimaryText(prediction);
                state.selectedPredictionPlaceId = prediction.placeId ?? null;
                renderStatus(els.searchStatus, 'info', `Loading details for ${candidateTitle}...`);
                showResultsPanel();

                try {
                    const place = prediction.toPlace();
                    await place.fetchFields({
                        fields: ['displayName', 'formattedAddress', 'location', 'viewport'],
                    });

                    const latitude = place.location?.lat?.();
                    const longitude = place.location?.lng?.();

                    if (!hasValidCoordinates(latitude, longitude)) {
                        throw new Error('Selected place does not include valid coordinates.');
                    }

                    const title = textToString(place.displayName) || candidateTitle;
                    const address = typeof place.formattedAddress === 'string' && place.formattedAddress.trim() !== ''
                        ? place.formattedAddress.trim()
                        : getPredictionSecondaryText(prediction) || candidateTitle;

                    els.searchInput.value = title;
                    els.address.value = address;
                    renderSelectedCandidate(title, address);
                    commitLocation(latitude, longitude, `Selected ${title} from the candidate list.`, {
                        preserveSelectedCandidate: true,
                        viewport: place.viewport ?? null,
                    });

                    renderStatus(els.searchStatus, 'success', `Selected ${title}. The map, coordinates, and timezone are updating now.`);
                    state.candidatePredictions = [];
                    hideResultsPanel();
                    refreshSearchSessionToken();
                } catch (error) {
                    renderStatus(els.searchStatus, 'error', 'The selected place could not be loaded. Try another candidate or refine the query.');
                    showResultsPanel();
                }
            }

            function renderSearchLoading(query) {
                showResultsPanel();
                clearResultsList();
                els.resultsCount.textContent = '...';
                els.resultsMeta.textContent = `Searching candidate locations for "${query}"...`;
                els.resultsEmpty.textContent = '';
                els.resultsEmpty.classList.add('hidden');
                renderStatus(els.searchStatus, 'info', 'Searching Google Maps for multiple candidate locations...');
            }

            function renderCandidateResults(query, predictions) {
                showResultsPanel();
                clearResultsList();
                els.resultsCount.textContent = `${predictions.length} option${predictions.length === 1 ? '' : 's'}`;
                els.resultsMeta.textContent = `Search results for "${query}". Choose one candidate before the map updates.`;

                if (predictions.length === 0) {
                    els.resultsEmpty.textContent = 'No candidate locations matched this query. Try a clearer business name, a nearby landmark, or more address detail.';
                    els.resultsEmpty.classList.remove('hidden');
                    renderStatus(els.searchStatus, 'warning', 'No candidate locations were found for this query yet.');
                    return;
                }

                els.resultsEmpty.classList.add('hidden');
                predictions.forEach((prediction) => {
                    els.resultsList.appendChild(createCandidateListItem(prediction));
                });
                renderStatus(els.searchStatus, 'success', `Found ${predictions.length} candidate location${predictions.length === 1 ? '' : 's'}. Choose the correct one before the map updates.`);
            }

            function renderSearchError(query, error = null) {
                const errorMessage = extractErrorMessage(error);

                showResultsPanel();
                clearResultsList();
                els.resultsCount.textContent = '0 options';
                els.resultsMeta.textContent = `Google Maps could not return candidate locations for "${query}".`;
                els.resultsEmpty.textContent = errorMessage
                    ? `Google Places returned an error: ${errorMessage}`
                    : 'Search is temporarily unavailable. Check the Places API (New) configuration, then try again.';
                els.resultsEmpty.classList.remove('hidden');
                renderStatus(
                    els.searchStatus,
                    'error',
                    errorMessage
                        ? `Google Places search failed: ${errorMessage}`
                        : 'Google Places search failed. Verify the browser key can access Places API (New).'
                );
            }

            function extractErrorMessage(error) {
                if (typeof error === 'string') {
                    return error.trim();
                }

                if (typeof error?.message === 'string' && error.message.trim() !== '') {
                    return error.message.trim();
                }

                if (typeof error?.cause?.message === 'string' && error.cause.message.trim() !== '') {
                    return error.cause.message.trim();
                }

                if (typeof error?.status === 'string' && error.status.trim() !== '') {
                    return error.status.trim();
                }

                if (typeof error?.code === 'string' && error.code.trim() !== '') {
                    return error.code.trim();
                }

                const asString = typeof error?.toString === 'function' ? error.toString().trim() : '';
                return asString && asString !== '[object Object]' ? asString : '';
            }

            function createCandidateListItem(prediction) {
                const item = document.createElement('li');
                const button = document.createElement('button');
                const primary = document.createElement('div');
                const secondary = document.createElement('p');
                const meta = document.createElement('div');
                const badge = document.createElement('span');
                const distance = document.createElement('span');
                const isSelected = prediction.placeId && prediction.placeId === state.selectedPredictionPlaceId;
                const primaryText = getPredictionPrimaryText(prediction);
                const secondaryText = getPredictionSecondaryText(prediction);
                const distanceText = formatPredictionDistance(prediction.distanceMeters);

                button.type = 'button';
                button.className = `group w-full px-4 py-3 text-left transition hover:bg-sky-50 focus:bg-sky-50 focus:outline-none ${isSelected ? 'bg-emerald-50 ring-1 ring-emerald-200' : ''}`;
                button.addEventListener('click', () => handleCandidateSelection(prediction));

                primary.className = 'text-sm font-semibold text-gray-900';
                primary.textContent = primaryText;

                secondary.className = 'mt-1 text-sm text-gray-600';
                secondary.textContent = secondaryText || 'Google Maps place prediction';

                meta.className = 'mt-2 flex flex-wrap items-center gap-2 text-xs font-medium text-gray-500';

                badge.className = `inline-flex items-center rounded-full px-2.5 py-1 ${isSelected ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'}`;
                badge.textContent = isSelected ? 'Current choice' : 'Select this location';
                meta.appendChild(badge);

                if (distanceText) {
                    distance.textContent = distanceText;
                    meta.appendChild(distance);
                }

                button.appendChild(primary);
                button.appendChild(secondary);
                button.appendChild(meta);
                item.appendChild(button);

                return item;
            }

            function renderSelectedCandidate(title, address) {
                els.selectedPanel.classList.remove('hidden');
                els.selectedTitle.textContent = title || 'Selected candidate';
                els.selectedAddress.textContent = address || 'Address details will appear here after selection.';
            }

            function clearSelectedCandidate() {
                state.selectedPredictionPlaceId = null;
                els.selectedTitle.textContent = '';
                els.selectedAddress.textContent = '';
                els.selectedPanel.classList.add('hidden');
            }

            function showResultsPanel() {
                els.resultsPanel.classList.remove('hidden');
            }

            function hideResultsPanel() {
                els.resultsPanel.classList.add('hidden');
            }

            function resultsPanelIsHidden() {
                return els.resultsPanel.classList.contains('hidden');
            }

            function clearResultsList() {
                els.resultsList.innerHTML = '';
                els.resultsEmpty.classList.add('hidden');
            }

            function buildAutocompleteBiasFromMap() {
                const origin = getAutocompleteOrigin();
                const radius = calculateBiasRadius(origin, state.map?.getBounds?.() ?? null);

                return {
                    center: origin,
                    radius,
                };
            }

            function getAutocompleteOrigin() {
                const mapCenter = state.map?.getCenter?.();
                if (mapCenter) {
                    return {
                        lat: mapCenter.lat(),
                        lng: mapCenter.lng(),
                    };
                }

                const latitude = getLatitudeValue();
                const longitude = getLongitudeValue();
                if (hasValidCoordinates(latitude, longitude)) {
                    return { lat: latitude, lng: longitude };
                }

                return state.defaultPosition;
            }

            function calculateBiasRadius(center, bounds) {
                const minimumRadius = Math.max(3000, getRadiusValue() * 8);
                const maximumRadius = 100000;

                if (!bounds) {
                    return minimumRadius;
                }

                const northEast = bounds.getNorthEast?.();
                const southWest = bounds.getSouthWest?.();
                if (!northEast || !southWest) {
                    return minimumRadius;
                }

                const latSpanMeters = Math.abs(northEast.lat() - southWest.lat()) * 111320;
                let lngSpan = Math.abs(northEast.lng() - southWest.lng());
                if (lngSpan > 180) {
                    lngSpan = 360 - lngSpan;
                }

                const lngSpanMeters = lngSpan * 111320 * Math.max(Math.cos(center.lat * (Math.PI / 180)), 0.2);
                const viewportRadius = Math.max(latSpanMeters, lngSpanMeters) / 2;

                if (!Number.isFinite(viewportRadius) || viewportRadius <= 0) {
                    return minimumRadius;
                }

                return Math.round(Math.min(Math.max(viewportRadius, minimumRadius), maximumRadius));
            }

            function getPredictionPrimaryText(prediction) {
                return textToString(prediction.mainText)
                    || prediction.text?.toString?.()
                    || 'Unknown place';
            }

            function getPredictionSecondaryText(prediction) {
                return textToString(prediction.secondaryText);
            }

            function formatPredictionDistance(distanceMeters) {
                if (!Number.isFinite(distanceMeters) || distanceMeters <= 0) {
                    return '';
                }

                if (distanceMeters < 1000) {
                    return `${Math.round(distanceMeters)} m from current map center`;
                }

                return `${(distanceMeters / 1000).toFixed(1)} km from current map center`;
            }

            function textToString(value) {
                if (typeof value === 'string') {
                    return value.trim();
                }

                if (typeof value?.toString === 'function') {
                    return value.toString().trim();
                }

                if (typeof value?.text === 'string') {
                    return value.text.trim();
                }

                return '';
            }

            function stabilizeMapRendering(startPosition) {
                const rerenderMap = debounce(() => {
                    if (!state.map) {
                        return;
                    }

                    const markerPosition = state.marker?.getPosition?.();
                    const center = markerPosition
                        ? { lat: markerPosition.lat(), lng: markerPosition.lng() }
                        : startPosition;

                    window.google.maps.event.trigger(state.map, 'resize');
                    state.map.setCenter(center);
                }, 120);

                window.setTimeout(rerenderMap, 150);
                window.addEventListener('resize', rerenderMap);
            }

            function bindRadiusInputs() {
                const sliderMax = Number.parseInt(els.radiusRange.max, 10) || 500;
                const manualMax = Number.parseInt(els.radiusInput.max, 10) || sliderMax;

                const syncRadius = (rawValue) => {
                    const radius = Math.max(1, Math.min(manualMax, Number.parseInt(rawValue, 10) || 1));

                    els.radiusInput.value = radius;
                    els.radiusRange.value = Math.min(radius, sliderMax);
                    updateRadiusPresentation(radius);
                    syncCircle(radius);
                };

                els.radiusInput.addEventListener('input', (event) => {
                    syncRadius(event.target.value);
                });

                els.radiusRange.addEventListener('input', (event) => {
                    syncRadius(event.target.value);
                });

                els.radiusPresets.forEach((button) => {
                    button.addEventListener('click', () => {
                        syncRadius(button.dataset.officeLocationRadiusPreset);
                    });
                });
            }

            function bindCoordinateInputs() {
                const handleCoordinateChange = () => {
                    window.clearTimeout(state.coordinateDebounce);
                    state.coordinateDebounce = window.setTimeout(() => {
                        const latitude = getLatitudeValue();
                        const longitude = getLongitudeValue();

                        updateSnapshot();

                        if (!hasValidCoordinates(latitude, longitude)) {
                            renderStatus(els.mapStatus, 'warning', 'Latitude must stay between -90 and 90, and longitude between -180 and 180.');
                            renderStatus(els.timezoneStatus, 'warning', 'Timezone lookup needs valid latitude and longitude.');
                            return;
                        }

                        commitLocation(latitude, longitude, 'Coordinates updated manually.', {
                            preserveZoom: true,
                        });
                        renderStatus(els.mapStatus, 'success', 'Coordinates updated. The map pin and timezone are refreshing now.');
                    }, 350);
                };

                els.latitude.addEventListener('input', handleCoordinateChange);
                els.longitude.addEventListener('input', handleCoordinateChange);
            }

            function bindTimezoneInput() {
                els.timezone.addEventListener('input', () => {
                    updateSnapshot();

                    if (els.timezone.value.trim() !== '') {
                        renderStatus(els.timezoneStatus, 'info', 'Timezone edited manually. You can still run automatic detection again if needed.');
                    }
                });

                els.timezoneRefresh.addEventListener('click', () => {
                    requestTimezoneResolution('Timezone detection requested manually.', { preserveTimezoneOnFailure: false });
                });
            }

            function commitLocation(latitude, longitude, timezoneReason, options = {}) {
                if (!hasValidCoordinates(latitude, longitude)) {
                    renderStatus(els.mapStatus, 'warning', 'The selected point does not contain valid coordinates.');
                    return;
                }

                const normalizedPosition = {
                    lat: Number(latitude),
                    lng: Number(longitude),
                };

                if (!options.preserveSelectedCandidate) {
                    clearSelectedCandidate();
                }

                els.latitude.value = formatCoordinate(normalizedPosition.lat);
                els.longitude.value = formatCoordinate(normalizedPosition.lng);
                updateSnapshot();
                syncMarkerAndCircle(normalizedPosition, options);
                requestTimezoneResolution(timezoneReason, options);
            }

            async function requestTimezoneResolution(reason, options = {}) {
                const latitude = getLatitudeValue();
                const longitude = getLongitudeValue();
                const preserveTimezoneOnFailure = Boolean(options.preserveTimezoneOnFailure);
                const requestId = ++state.pendingTimezoneRequest;

                if (!hasValidCoordinates(latitude, longitude)) {
                    if (!preserveTimezoneOnFailure) {
                        els.timezone.value = '';
                        updateSnapshot();
                    }

                    renderStatus(els.timezoneStatus, 'warning', 'Timezone lookup needs valid latitude and longitude.');
                    return;
                }

                renderStatus(els.timezoneStatus, 'info', 'Resolving timezone from the selected coordinates...');

                try {
                    const response = await fetch(config.timezoneResolveUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            latitude,
                            longitude,
                        }),
                    });

                    const payload = await response.json().catch(() => ({}));

                    if (requestId !== state.pendingTimezoneRequest) {
                        return;
                    }

                    if (response.ok && payload?.data?.resolved && typeof payload.data.timezone === 'string') {
                        els.timezone.value = payload.data.timezone;
                        updateSnapshot();
                        renderStatus(els.timezoneStatus, 'success', payload.message || `${reason} Timezone resolved automatically.`);
                        return;
                    }

                    if (!preserveTimezoneOnFailure) {
                        els.timezone.value = '';
                        updateSnapshot();
                    }

                    renderStatus(
                        els.timezoneStatus,
                        'error',
                        payload?.message || payload?.data?.message || 'No timezone could be determined for the selected point. Enter the timezone manually.'
                    );
                } catch (error) {
                    if (requestId !== state.pendingTimezoneRequest) {
                        return;
                    }

                    if (!preserveTimezoneOnFailure) {
                        els.timezone.value = '';
                        updateSnapshot();
                    }

                    renderStatus(els.timezoneStatus, 'warning', 'Timezone detection is temporarily unavailable. You can still enter the timezone manually.');
                }
            }

            function focusMap(position, options = {}) {
                if (!state.map) {
                    return;
                }

                if (options.viewport) {
                    state.map.fitBounds(options.viewport, 56);
                    return;
                }

                state.map.panTo(position);
                if (!options.preserveZoom && (state.map.getZoom() ?? 0) < 16) {
                    state.map.setZoom(16);
                }
            }

            function syncMarkerFromInputs(options = {}) {
                const latitude = getLatitudeValue();
                const longitude = getLongitudeValue();

                if (!hasValidCoordinates(latitude, longitude)) {
                    return;
                }

                syncMarkerAndCircle({ lat: latitude, lng: longitude }, options);
            }

            function syncMarkerAndCircle(position, options = {}) {
                if (!state.marker || !state.circle) {
                    return;
                }

                state.marker.setOpacity(1);
                state.marker.setPosition(position);
                state.circle.setCenter(position);
                syncCircle(getRadiusValue());
                focusMap(position, options);
            }

            function syncCircle(radius) {
                if (!state.circle) {
                    return;
                }

                state.circle.setRadius(radius);
            }

            function updateSnapshot() {
                const latitude = getLatitudeValue();
                const longitude = getLongitudeValue();

                els.latitudeDisplay.textContent = hasValidCoordinates(latitude, longitude)
                    ? formatCoordinate(latitude)
                    : 'Not selected yet';
                els.longitudeDisplay.textContent = hasValidCoordinates(latitude, longitude)
                    ? formatCoordinate(longitude)
                    : 'Not selected yet';
                els.timezoneDisplay.textContent = els.timezone.value.trim() || 'Waiting for lookup or manual entry';
                els.radiusDisplay.textContent = `${formatInteger(getRadiusValue())} meters`;
            }

            function updateRadiusPresentation(radius) {
                const sliderMax = Number.parseInt(els.radiusRange.max, 10) || 500;
                const manualMax = Number.parseInt(els.radiusInput.max, 10) || sliderMax;

                els.radiusChip.textContent = `${formatInteger(radius)} m`;
                els.radiusDisplay.textContent = `${formatInteger(radius)} meters`;

                if (els.radiusHelper) {
                    els.radiusHelper.textContent = radius > sliderMax
                        ? `Manual override active at ${formatInteger(radius)} meters. The quick slider stops at ${formatInteger(sliderMax)} meters, but the map still uses the full radius.`
                        : `Most office geofences work best between 50-300 meters. Slider covers the common range up to ${formatInteger(sliderMax)} meters, with manual input available up to ${formatInteger(manualMax)} meters.`;
                }

                els.radiusPresets.forEach((button) => {
                    const presetValue = Number.parseInt(button.dataset.officeLocationRadiusPreset, 10);
                    const isActive = presetValue === radius;

                    button.className = isActive
                        ? 'inline-flex items-center justify-center rounded-xl border border-sky-500 bg-sky-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition'
                        : 'inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-600 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700';
                });
            }

            function renderStatus(element, tone, message) {
                element.className = `rounded-xl border px-3 py-2 text-xs font-medium ${palette[tone] || palette.info}`;
                element.textContent = message;
            }

            function numericOrNull(value) {
                if (value === null || value === undefined || value === '') {
                    return null;
                }

                const numericValue = Number(value);
                return Number.isFinite(numericValue) ? numericValue : null;
            }

            function getLatitudeValue() {
                return numericOrNull(els.latitude.value);
            }

            function getLongitudeValue() {
                return numericOrNull(els.longitude.value);
            }

            function getRadiusValue() {
                return Math.max(1, Math.min(Number.parseInt(els.radiusInput.max, 10) || 1000, Number.parseInt(els.radiusInput.value, 10) || 1));
            }

            function formatCoordinate(value) {
                return Number(value).toFixed(7);
            }

            function formatInteger(value) {
                return new Intl.NumberFormat().format(Number(value) || 0);
            }

            function hasValidCoordinates(latitude, longitude) {
                return Number.isFinite(latitude)
                    && Number.isFinite(longitude)
                    && latitude >= -90
                    && latitude <= 90
                    && longitude >= -180
                    && longitude <= 180;
            }

            function debounce(callback, wait) {
                let timeoutId = null;

                return (...args) => {
                    window.clearTimeout(timeoutId);
                    timeoutId = window.setTimeout(() => callback(...args), wait);
                };
            }
        });
    </script>
@endpush


