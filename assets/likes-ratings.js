document.addEventListener('DOMContentLoaded', function () {
    init();
});

function init() {
    document.querySelectorAll('[data-likes-ratings]').forEach(container => {
        const data = JSON.parse(container.getAttribute('data-likes-ratings'));

        if (container.hasAttribute('data-likes-readonly')) {
           return;
       }

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
                        if (response.status) {
                            const errorDiv = button.querySelectorAll('[data-likes-error]');
                            errorDiv.textContent = response.error;
                            container.outerHTML = response.content;
                            init();
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    });
}
