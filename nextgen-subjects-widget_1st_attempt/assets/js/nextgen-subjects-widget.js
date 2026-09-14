(() => {
    'use strict';

    class NextGenSubjects {
        constructor(root) {
            this.root = root;
            this.search = root.querySelector('[data-subject-search]');
            this.cards = Array.from(root.querySelectorAll('[data-subject-card]'));
            this.empty = root.querySelector('[data-subject-empty]');

            this.bindSearch();
            this.bindCardMotion();
        }

        bindSearch() {
            if (!this.search) return;

            this.search.addEventListener('input', () => {
                const query = this.search.value.toLocaleLowerCase().trim();
                let visibleCount = 0;

                this.cards.forEach((card) => {
                    const value = (card.dataset.subjectSearchValue || '').toLocaleLowerCase();
                    const visible = query.length === 0 || value.includes(query);

                    card.hidden = !visible;
                    if (visible) visibleCount += 1;
                });

                if (this.empty) {
                    this.empty.hidden = visibleCount > 0;
                }
            });
        }

        bindCardMotion() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            if (window.matchMedia('(pointer: coarse)').matches) return;

            this.cards.forEach((card) => {
                card.addEventListener('mousemove', (event) => {
                    const rect = card.getBoundingClientRect();
                    const x = (event.clientX - rect.left) / rect.width;
                    const y = (event.clientY - rect.top) / rect.height;
                    const rotateY = (x - 0.5) * 7;
                    const rotateX = (0.5 - y) * 7;

                    card.style.setProperty('--ng-rotate-x', `${rotateX.toFixed(2)}deg`);
                    card.style.setProperty('--ng-rotate-y', `${rotateY.toFixed(2)}deg`);
                });

                card.addEventListener('mouseleave', () => {
                    card.style.setProperty('--ng-rotate-x', '0deg');
                    card.style.setProperty('--ng-rotate-y', '0deg');
                });
            });
        }
    }

    const initialize = () => {
        document.querySelectorAll('[data-nextgen-subjects]').forEach((root) => {
            if (root.dataset.nextgenInitialized === 'true') return;
            root.dataset.nextgenInitialized = 'true';
            new NextGenSubjects(root);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
})();
