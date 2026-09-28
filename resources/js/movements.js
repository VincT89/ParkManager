export async function toggleMovement(reservationId, type, checkbox) {
    const previousValue = !checkbox.checked;
    const errorBox = document.getElementById('pm-request-error');
    errorBox.hidden = true;
    checkbox.disabled = true;
    try {
        const response = await fetch(`/reservations/${reservationId}/toggle-movement`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ type, value: checkbox.checked }),
        });
        if (!response.ok) {
            throw new Error(response.status === 409
                ? 'Il parcheggio attivo è cambiato in un’altra scheda. Ricarica la pagina prima di registrare il movimento.'
                : 'Il movimento non è stato salvato. Riprova.');
        }
    } catch (error) {
        checkbox.checked = previousValue;
        errorBox.textContent = error instanceof TypeError
            ? 'Connessione interrotta: il movimento non è stato salvato. Riprova.'
            : error.message;
        errorBox.hidden = false;
        errorBox.scrollIntoView({ block: 'nearest' });
    } finally {
        checkbox.disabled = false;
    }
}
