@props(['value' => 'shared', 'id' => 'capacity_mode'])
@php
    $descriptions = [
        'shared' => 'Le categorie utilizzano lo stesso totale di posti. Si rispettano anche i limiti impostati per ciascuna categoria.',
        'per_product' => 'Ogni categoria ha una disponibilità propria, ad esempio posti coperti e scoperti. I posti riservati vanno assegnati a una categoria.',
    ];
@endphp
<div class="pm-form-group" x-data="{ mode: @js($value) }">
    <label class="pm-label" for="{{ $id }}">Come sono organizzati i posti?</label>
    <select id="{{ $id }}" name="capacity_mode" class="pm-select" x-model="mode" aria-describedby="{{ $id }}-help" required>
        <option value="shared" @selected($value === 'shared')>Posti condivisi tra le categorie</option>
        <option value="per_product" @selected($value === 'per_product')>Posti separati per categoria</option>
    </select>
    <p id="{{ $id }}-help" class="pm-form-note" x-text="mode === 'shared' ? @js($descriptions['shared']) : @js($descriptions['per_product'])">{{ $descriptions[$value] ?? $descriptions['shared'] }}</p>
    @error('capacity_mode') <p class="pm-form-error" role="alert">{{ $message }}</p> @enderror
</div>
