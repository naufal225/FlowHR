@props([
    'myLabel' => 'My Requests',
    'allLabel' => 'All Requests',
])

<div class="request-tabs" data-request-tabs>
    <button type="button" class="request-tabs__button is-active" data-request-tab-trigger="my">
        {{ $myLabel }}
    </button>
    <button type="button" class="request-tabs__button" data-request-tab-trigger="all">
        {{ $allLabel }}
    </button>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const initRequestTabs = () => {
                    const tabNavigations = document.querySelectorAll('[data-request-tabs]');

                    if (!tabNavigations.length) {
                        return;
                    }

                    tabNavigations.forEach((navigation, navigationIndex) => {
                        if (navigation.dataset.requestTabsReady === 'true') {
                            return;
                        }

                        const myPanels = [];
                        const allPanels = [];
                        let nextElement = navigation.nextElementSibling;

                        while (nextElement) {
                            const panelType = nextElement.getAttribute('data-request-tab-panel');

                            if (panelType === 'my') {
                                myPanels.push(nextElement);
                            } else if (panelType === 'all') {
                                allPanels.push(nextElement);
                            }

                            nextElement = nextElement.nextElementSibling;
                        }

                        if (!myPanels.length || !allPanels.length) {
                            return;
                        }

                        const triggers = navigation.querySelectorAll('[data-request-tab-trigger]');
                        const storageKey = `request-tab:${window.location.pathname}:${navigationIndex}`;
                        const params = new URLSearchParams(window.location.search);
                        const hasAllPageParameter = Array.from(params.keys()).some((key) => key.startsWith('all_page'));
                        let storedTab = 'my';

                        try {
                            storedTab = sessionStorage.getItem(storageKey) || 'my';
                        } catch (error) {
                            // Ignore storage access errors.
                        }

                        const initialTab = (params.get('tab') === 'all' || hasAllPageParameter)
                            ? 'all'
                            : storedTab;

                        const setActiveTab = (activeTab) => {
                            const showMyPanel = activeTab === 'my';

                            myPanels.forEach((panel) => panel.classList.toggle('hidden', !showMyPanel));
                            allPanels.forEach((panel) => panel.classList.toggle('hidden', showMyPanel));

                            triggers.forEach((trigger) => {
                                const isActive = trigger.getAttribute('data-request-tab-trigger') === activeTab;
                                trigger.classList.toggle('is-active', isActive);
                                trigger.setAttribute('aria-selected', isActive ? 'true' : 'false');
                            });

                            try {
                                sessionStorage.setItem(storageKey, activeTab);
                            } catch (error) {
                                // Ignore storage errors (private mode / blocked storage).
                            }
                        };

                        triggers.forEach((trigger) => {
                            trigger.addEventListener('click', () => {
                                const targetPanel = trigger.getAttribute('data-request-tab-trigger');

                                if (targetPanel !== 'my' && targetPanel !== 'all') {
                                    return;
                                }

                                setActiveTab(targetPanel);
                            });
                        });

                        setActiveTab(initialTab === 'all' ? 'all' : 'my');
                        navigation.dataset.requestTabsReady = 'true';
                    });
                };

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initRequestTabs);
                } else {
                    initRequestTabs();
                }
            })();
        </script>
    @endpush
@endonce
