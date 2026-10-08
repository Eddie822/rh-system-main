<script>
    window.confirmDeletion = async function (element) {
        const dark = document.documentElement.classList.contains('dark');
        const action = element.dataset.deleteAction || 'Eliminar';
        const label = element.dataset.deleteLabel || 'este elemento';
        const name = element.dataset.deleteName || '';
        const result = await Swal.fire({
            title: `¿${action} ${label}?`,
            text: name || 'Confirma que deseas continuar.',
            icon: 'warning',
            showCancelButton: true,
            focusCancel: true,
            confirmButtonText: action,
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            background: dark ? '#1f2937' : '#ffffff',
            color: dark ? '#f9fafb' : '#111827',
        });

        return result.isConfirmed;
    };

    window.confirmDeleteForm = async function (event, form) {
        event.preventDefault();
        if (await window.confirmDeletion(form)) {
            form.submit();
        }
    };

    window.confirmLivewireDeletion = async function (event, button, method, ...args) {
        event.preventDefault();
        event.stopPropagation();
        if (!await window.confirmDeletion(button)) return;

        let root = button;
        while (root && !root.hasAttribute('wire:id')) root = root.parentElement;
        if (root) {
            await window.Livewire.find(root.getAttribute('wire:id')).call(method, ...args);
        }
    };
</script>
@if (session('deleted'))
    <script>
        Swal.fire({
            title: 'Eliminado',
            text: @js(session('deleted')),
            icon: 'success',
            confirmButtonText: 'Aceptar',
            background: document.documentElement.classList.contains('dark') ? '#1f2937' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f9fafb' : '#111827',
        });
    </script>
@endif
