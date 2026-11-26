@props(['parking' => null])
@php($prices = $parking ?? new \App\Models\Parking)
<fieldset class="pm-settings-section">
    <legend>Prezzi del lavaggio</legend>
    <p id="car-wash-settings-help" class="pm-form-note">Importi in euro, applicati una sola volta per prenotazione. I nuovi prezzi valgono per le prenotazioni successive; quelle già registrate conservano l’importo originale.</p>
    <div class="pm-settings-grid">
        @foreach (\App\Enums\CarWashType::cases() as $type)
            @php($attribute = $type->priceAttribute())
            <div class="pm-form-group">
                <label class="pm-label" for="{{ $attribute }}">{{ $type->label() }} (€)</label>
                <input id="{{ $attribute }}" name="{{ $attribute }}" type="number" class="pm-input" value="{{ old($attribute, $prices->getAttribute($attribute)) }}" min="0" max="99999999.99" step="0.01" inputmode="decimal" required aria-describedby="car-wash-settings-help{{ $errors->has($attribute) ? ' '.$attribute.'-error' : '' }}" @if($errors->has($attribute)) aria-invalid="true" @endif>
                @error($attribute) <p id="{{ $attribute }}-error" class="pm-form-error" role="alert">{{ $message }}</p> @enderror
            </div>
        @endforeach
    </div>
</fieldset>
