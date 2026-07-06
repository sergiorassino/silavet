import './bootstrap';
import Swal from 'sweetalert2';

window.Swal = Swal;

window.vlSwalExito = (mensaje, titulo = 'Listo') => {
    return Swal.fire({
        icon: 'success',
        title: titulo,
        text: mensaje,
        confirmButtonColor: '#0284c7',
    });
};

window.vlSwalError = (mensaje, titulo = 'Error') => {
    return Swal.fire({
        icon: 'error',
        title: titulo,
        text: mensaje,
        confirmButtonColor: '#0284c7',
    });
};

window.vlSwalConfirmar = function (mensaje, titulo = '¿Confirma?', opciones = {}) {
    if (typeof Swal === 'undefined') {
        return Promise.resolve(window.confirm(mensaje));
    }

    return Swal.fire({
        icon: 'question',
        title: titulo,
        text: mensaje,
        showCancelButton: true,
        confirmButtonText: opciones.confirmButtonText ?? 'Sí, confirmar',
        cancelButtonText: opciones.cancelButtonText ?? 'Cancelar',
        confirmButtonColor: '#0284c7',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusCancel: true,
        ...opciones,
    }).then((result) => result.isConfirmed === true);
};

document.addEventListener('livewire:init', () => {
    Livewire.on('vl-swal-exito', ({ mensaje, titulo }) => {
        window.vlSwalExito(mensaje, titulo ?? 'Listo');
    });

    Livewire.on('vl-swal-error', ({ mensaje, titulo }) => {
        window.vlSwalError(mensaje, titulo ?? 'Error');
    });
});
