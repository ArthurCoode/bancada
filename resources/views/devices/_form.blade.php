<x-field name="brand" label="Marca" :value="$device?->brand" list="brands" required autofocus />
<datalist id="brands">
    @foreach (['Samsung', 'Apple', 'Motorola', 'Xiaomi', 'LG', 'Realme', 'Asus', 'Positivo', 'Lenovo', 'Dell', 'Acer'] as $brand)
        <option value="{{ $brand }}"></option>
    @endforeach
</datalist>

<x-field name="model" label="Modelo" :value="$device?->model" required />
<x-field name="serial_number" label="IMEI / Número de série (opcional)" :value="$device?->serial_number" />
<x-field name="color" label="Cor (opcional)" :value="$device?->color" />

<div>
    <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Observações (opcional)</label>
    <textarea id="notes" name="notes" rows="3"
              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">{{ old('notes', $device?->notes) }}</textarea>
    @error('notes')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

<div class="flex items-center gap-3">
    <button type="submit"
            class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
        {{ $submitLabel }}
    </button>
    <a href="{{ $cancelUrl }}" class="text-sm text-slate-600 underline">Cancelar</a>
</div>