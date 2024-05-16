document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-likes-ratings]').forEach(container => {
      const data = JSON.parse(container.getAttribute('data-likes-ratings'));
      const readOnly = data.options.readOnly;
      const disableAfterRate = data.options.disableAfterRate;

      container.querySelectorAll('[data-likes-type]').forEach(button => {
        button.addEventListener('click', function() {
          if (readOnly || container.getAttribute('data-likes-disable') === 'true') return;

          const type = button.getAttribute('data-likes-type');

          fetch(data.uri, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: data.id, type: type }),
          })
          .then(response => response.json())
          .then(response => {
            if (response.status) {
              const statusSpan = button.querySelector('[data-likes-status]');
              statusSpan.textContent = response.count;
              if (disableAfterRate) {
                container.setAttribute('data-likes-disable', 'true');
              }
            }
          })
          .catch(error => console.error('Error:', error));
        });
      });
    });
  });
