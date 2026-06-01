document.addEventListener('DOMContentLoaded', function () {
    init();
});

function init(root = document) {
    root.querySelectorAll('[data-likes-ratings]').forEach(bindContainer);

    // After an AJAX update the replaced node is itself the container, so bind it too.
    if (root.nodeType === Node.ELEMENT_NODE && root.matches('[data-likes-ratings]')) {
        bindContainer(root);
    }
}

function bindContainer(container) {
    // Guard against binding the same container twice (init() can run repeatedly).
    if (container.dataset.likesBound) {
        return;
    }
    container.dataset.likesBound = '1';

    if (container.hasAttribute('data-likes-readonly')) {
        return;
    }

    const data = JSON.parse(container.getAttribute('data-likes-ratings'));

    container.querySelectorAll('[data-likes-type]').forEach(button => {
        button.addEventListener('click', function () {
            const type = button.getAttribute('data-likes-type');
            fetch(data.uri, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({id: data.id, type: type}),
            })
                .then(response => response.json())
                .then(response => {
                    if (!response.status) {
                        return;
                    }

                    // response.content is the full rendered template, which may wrap the
                    // widget in extra markup (e.g. a label). Swap only the matching
                    // [data-likes-ratings] node so surrounding, render-once markup is left
                    // untouched instead of being duplicated.
                    const fragment = document.createElement('div');
                    fragment.innerHTML = response.content.trim();
                    const fresh = fragment.querySelector('[data-likes-ratings]') || fragment.firstElementChild;

                    if (fresh) {
                        container.replaceWith(fresh);
                        init(fresh);
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });
}
