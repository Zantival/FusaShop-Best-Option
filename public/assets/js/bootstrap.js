// Configuración de Axios para peticiones AJAX
if (window.axios) {
    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
}
