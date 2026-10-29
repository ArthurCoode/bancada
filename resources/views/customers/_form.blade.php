<x-field name="name" label="Nome" :value="$customer?->name" required autofocus />

<x-field name="phone" label="Telefone / WhatsApp (com DDD)" type="tel"
         :value="$customer?->formattedPhone()" placeholder="(11) 98765-4321" required />

<x-field name="email" label="E-mail (opcional)" type="email" :value="$customer?->email" />

<div>
    <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Observações (opcional)</label>
    <textarea id="notes" name="notes" rows="3"
              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-slate-500 focus:outline-none">{{ old('notes', $customer?->notes) }}</textarea>
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