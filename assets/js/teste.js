function testClient(key) {
    const clientData = {
        subject: document.querySelector(`input[name="clients[${key}][subject]"]`).value,
        name: document.querySelector(`input[name="clients[${key}][name]"]`).value,
        volume_name: document.querySelector(`input[name="clients[${key}][volume_name]"]`).value,
        emails: document.querySelector(`textarea[name="clients[${key}][emails]"]`).value,
        default_checkbox: document.querySelector(`input[name="clients[${key}][default_checkbox]"]`).checked ? '1' : '0',
        checked_checkbox: document.querySelector(`input[name="clients[${key}][checked_checkbox]"]`).checked ? '1' : '0',
    };

    // Limpar qualquer mensagem existente
    const toastContainer = document.getElementById('toastContainer');
    toastContainer.innerHTML = '';

    // Mostrar mensagem de envio no formato toast
    const toastSending = `
        <div class="toast align-items-center text-bg-primary border-0 position-fixed start-50 top-50 translate-middle" role="alert" aria-live="assertive" aria-atomic="true" id="alertToast">
            <div class="d-flex">
                <div class="toast-body">Enviando...</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close">
                    <i class="fa-solid fa-circle-xmark fa-lg"></i>
                </button>
            </div>
        </div>
    `;
    toastContainer.innerHTML = toastSending;
    const toast = new bootstrap.Toast(document.getElementById('alertToast'));
    toast.show();



    fetch('script.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(clientData),
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const toastMessage = `
                <div class="toast align-items-center ${data.success ? 'text-bg-success' : 'text-bg-danger'} border-0 position-fixed start-50 top-50 translate-middle" role="alert" aria-live="assertive" aria-atomic="true" id="alertToast">
                    <div class="d-flex">
                        <div class="toast-body">${data.message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close">
                            <i class="fa-solid fa-circle-xmark fa-lg"></i>
                        </button>
                    </div>
                </div>
            `;
            toastContainer.innerHTML = toastMessage;
            const toast = new bootstrap.Toast(document.getElementById('alertToast'));
            toast.show();
        })
        .catch(error => {
            const toastError = `
                <div class="toast align-items-center text-bg-danger border-0 position-fixed start-50 top-50 translate-middle" role="alert" aria-live="assertive" aria-atomic="true" id="alertToast">
                    <div class="d-flex">
                        <div class="toast-body">Erro ao testar: ${error.message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close">
                            <i class="fa-solid fa-circle-xmark fa-lg"></i>
                        </button>
                    </div>
                </div>
            `;
            toastContainer.innerHTML = toastError;
            const toast = new bootstrap.Toast(document.getElementById('alertToast'));
            toast.show();
        });
}
