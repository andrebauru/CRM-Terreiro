document.addEventListener('DOMContentLoaded', function () {
    const mainModal = new bootstrap.Modal(document.getElementById('mainModal'));
    const mainModalElement = document.getElementById('mainModal');
    const mainModalTitle = document.getElementById('mainModalLabel');
    const mainModalBody = mainModalElement.querySelector('.modal-body');

    // Lidar com cliques em botões que abrem modais
    document.body.addEventListener('click', function(event) {
        const triggerButton = event.target.closest('[data-bs-toggle="modal"][data-bs-target="#mainModal"]');
        if (triggerButton) {
            event.preventDefault();

            const url = triggerButton.getAttribute('data-url');
            const title = triggerButton.getAttribute('data-title');

            mainModalTitle.textContent = title;
            mainModalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border" role="status"></div></div>';
            mainModal.show();

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erro ao carregar: ' + response.status);
                    }
                    return response.text();
                })
                .then(html => {
                    mainModalBody.innerHTML = html;
                    // Re-executar scripts se houver algum no conteúdo carregado
                    const scripts = mainModalBody.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        if (script.src) {
                            newScript.src = script.src;
                        } else {
                            newScript.textContent = script.innerHTML;
                        }
                        document.body.appendChild(newScript).parentNode.removeChild(newScript);
                    });
                })
                .catch(error => {
                    mainModalBody.innerHTML = `<div class="alert alert-danger">Erro ao carregar conteúdo: ${error}</div>`;
                });
        }
    });

    // Lidar com o envio de formulários dentro da modal
    mainModalBody.addEventListener('submit', function(event) {
        const form = event.target.closest('form');
        if (form) {
            event.preventDefault();
            const formData = new FormData(form);
            const url = form.getAttribute('action');
            const method = form.getAttribute('method');

            fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mainModal.hide();
                    // Recarregar a página para ver as alterações
                    window.location.reload();
                } else {
                    // Exibir erros no formulário
                    const errorContainer = form.querySelector('.form-errors');
                    if (errorContainer) {
                        let errorHtml = '<div class="alert alert-danger"><ul>';
                        for (const error of data.errors) {
                            errorHtml += `<li>${error}</li>`;
                        }
                        errorHtml += '</ul></div>';
                        errorContainer.innerHTML = errorHtml;
                    }
                }
            })
            .catch(error => {
                console.error('Erro no formulário:', error);
            });
        }
    });

    // Handle Settings Form Submission
    const settingsForm = document.getElementById('settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function (event) {
            event.preventDefault();
            const form = event.target;
            const submitButton = form.querySelector('button[type="submit"]');
            const spinner = submitButton.querySelector('.spinner-border');
            const alertPlaceholder = document.getElementById('settings-alert-placeholder');

            submitButton.disabled = true;
            spinner.classList.remove('d-none');
            alertPlaceholder.innerHTML = '';

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('success', data.message, alertPlaceholder);
                    // Update logo on the fly if a new one was uploaded
                    if (data.new_logo_url) {
                        const logoImg = document.getElementById('current-logo');
                        const sidebarLogo = document.querySelector('.navbar-brand-img');
                        if (logoImg) {
                            logoImg.src = data.new_logo_url;
                        }
                        if (sidebarLogo) {
                            sidebarLogo.src = data.new_logo_url;
                        }
                    }
                } else {
                    const errorMsg = data.errors ? data.errors.join('<br>') : 'Ocorreu um erro desconhecido.';
                    showAlert('danger', errorMsg, alertPlaceholder);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'Ocorreu um erro de comunicação com o servidor.', alertPlaceholder);
            })
            .finally(() => {
                submitButton.disabled = false;
                spinner.classList.add('d-none');
            });
        });
    }

    function showAlert(type, message, placeholder) {
        const wrapper = document.createElement('div');
        wrapper.innerHTML = [
            `<div class="alert alert-${type} alert-dismissible" role="alert">`,
            `   <div>${message}</div>`,
            '   <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`,
            '</div>'
        ].join('');
        placeholder.append(wrapper);
    }
});
