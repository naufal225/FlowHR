@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-office-location-detail-map]');
            if (!root) {
                return;
            }

            const mapElement = root.querySelector('#office-location-detail-map');
            const statusElement = root.querySelector('[data-office-location-detail-map-status]');
            if (!mapElement || !statusElement) {
                return;
            }

            const configRaw = root.dataset.config;
            let config = {};

            try {
                config = configRaw ? JSON.parse(configRaw) : {};
            } catch (error) {
                renderStatus('error', 'Office coverage map config is invalid. Please refresh the page.');
                return;
            }

            const officeLatitude = numericOrNull(config.office?.latitude);
            const officeLongitude = numericOrNull(config.office?.longitude);
            const officeRadiusMeter = Math.max(1, Number.parseInt(config.office?.radiusMeter, 10) || 1);
            const browserKey = typeof config.googleMapsBrowserKey === 'string'
                ? config.googleMapsBrowserKey.trim()
                : '';

            if (!hasValidCoordinates(officeLatitude, officeLongitude)) {
                renderStatus('warning', 'Office coordinates are unavailable, so the map radius preview cannot be rendered.');
                return;
            }

            if (browserKey === '') {
                renderStatus('warning', 'Google Maps is not configured. Configure GOOGLE_MAPS_BROWSER_KEY to render the office coverage map.');
                return;
            }

            registerGoogleMapsAuthFailureHandler();
            renderStatus('info', 'Loading Google Maps office coverage preview...');

            loadGoogleMapsApi(browserKey)
                .then(async () => {
                    const { Map, Circle } = await window.google.maps.importLibrary('maps');
                    const officePosition = { lat: officeLatitude, lng: officeLongitude };

                    const map = new Map(mapElement, {
                        center: officePosition,
                        zoom: 16,
                        mapTypeControl: false,
                        streetViewControl: false,
                        fullscreenControl: false,
                        clickableIcons: false,
                        gestureHandling: 'greedy',
                    });

                    const marker = new window.google.maps.Marker({
                        map,
                        position: officePosition,
                        draggable: false,
                        clickable: false,
                        title: typeof config.office?.name === 'string' && config.office.name.trim() !== ''
                            ? config.office.name.trim()
                            : 'Office location',
                    });

                    const circle = new Circle({
                        map,
                        center: officePosition,
                        radius: officeRadiusMeter,
                        strokeColor: '#2563eb',
                        strokeOpacity: 0.9,
                        strokeWeight: 2,
                        fillColor: '#60a5fa',
                        fillOpacity: 0.2,
                        clickable: false,
                    });

                    const circleBounds = circle.getBounds();
                    if (circleBounds) {
                        map.fitBounds(circleBounds, 56);
                    } else {
                        map.setCenter(officePosition);
                        map.setZoom(16);
                    }

                    window.google.maps.event.addListenerOnce(map, 'tilesloaded', () => {
                        renderStatus('success', 'Showing saved office pin and attendance radius preview.');
                    });

                    marker.setMap(map);
                })
                .catch(() => {
                    renderStatus('error', 'Google Maps failed to load. Check browser-key restrictions and Maps JavaScript API access.');
                });

            function loadGoogleMapsApi(key) {
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
                        key,
                        v: 'weekly',
                        loading: 'async',
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

            function registerGoogleMapsAuthFailureHandler() {
                const previousAuthFailureHandler = window.gm_authFailure;

                window.gm_authFailure = () => {
                    renderStatus('error', 'Google Maps rejected the browser key. Check referrer restrictions and Maps JavaScript API access.');

                    if (typeof previousAuthFailureHandler === 'function') {
                        previousAuthFailureHandler();
                    }
                };
            }

            function renderStatus(tone, message) {
                const palette = {
                    info: 'border-sky-200 bg-sky-50 text-sky-700',
                    success: 'border-emerald-200 bg-emerald-50 text-emerald-700',
                    warning: 'border-amber-200 bg-amber-50 text-amber-700',
                    error: 'border-rose-200 bg-rose-50 text-rose-700',
                };

                statusElement.className = `rounded-xl border px-4 py-3 text-xs font-medium ${palette[tone] || palette.info}`;
                statusElement.textContent = message;
            }

            function numericOrNull(value) {
                if (value === null || value === undefined || value === '') {
                    return null;
                }

                const numericValue = Number(value);
                return Number.isFinite(numericValue) ? numericValue : null;
            }

            function hasValidCoordinates(latitude, longitude) {
                return Number.isFinite(latitude)
                    && Number.isFinite(longitude)
                    && latitude >= -90
                    && latitude <= 90
                    && longitude >= -180
                    && longitude <= 180;
            }
        });
    </script>
@endpush
