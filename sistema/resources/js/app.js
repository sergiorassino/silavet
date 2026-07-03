import './bootstrap';
import Swal from 'sweetalert2';

window.Swal = Swal;

window.vlSwalExito = (mensaje, titulo = 'Listo') => {
    return Swal.fire({
        icon: 'success',
        title: titulo,
        text: mensaje,
        confirmButtonColor: '#2d6a6a',
    });
};

window.vlSwalError = (mensaje, titulo = 'Error') => {
    return Swal.fire({
        icon: 'error',
        title: titulo,
        text: mensaje,
        confirmButtonColor: '#2d6a6a',
    });
};

document.addEventListener('livewire:init', () => {
    Livewire.on('vl-swal-exito', ({ mensaje, titulo }) => {
        window.vlSwalExito(mensaje, titulo ?? 'Listo');
    });

    Livewire.on('vl-swal-error', ({ mensaje, titulo }) => {
        window.vlSwalError(mensaje, titulo ?? 'Error');
    });
});
