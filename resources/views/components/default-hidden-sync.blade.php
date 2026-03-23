<script>
    document.addEventListener('DOMContentLoaded', () => {
        const defaultHiddenElements = document.querySelectorAll('[data-inline-hidden]');

        if (!defaultHiddenElements.length) {
            return;
        }

        const syncInlineHiddenState = (element) => {
            if (element.classList.contains('hidden')) {
                element.style.display = 'none';
                return;
            }

            element.style.removeProperty('display');
        };

        defaultHiddenElements.forEach((element) => {
            syncInlineHiddenState(element);
        });

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (!(mutation.target instanceof HTMLElement)) {
                    return;
                }

                syncInlineHiddenState(mutation.target);
            });
        });

        defaultHiddenElements.forEach((element) => {
            observer.observe(element, {
                attributes: true,
                attributeFilter: ['class'],
            });
        });
    });
</script>
