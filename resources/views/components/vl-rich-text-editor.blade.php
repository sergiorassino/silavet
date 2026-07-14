@props([
    'wireProperty' => 'avisoTexto',
    'initial' => '',
    'maxLength' => 255,
    'placeholder' => 'Escriba el texto…',
    'label' => 'Texto',
    'labelId' => null,
    'errorBag' => null,
    'toolbarAriaLabel' => 'Formato del texto',
    'surfaceClass' => '',
    'saveMethod' => null,
    'showCounter' => true,
])

@php
    $labelId = $labelId ?? ($wireProperty.'-label');
    $errorBag = $errorBag ?? $wireProperty;
@endphp

<div {{ $attributes->class('vl-form-field') }}
     x-data="vlRichTextEditor({
         initial: @js($initial),
         maxLength: {{ (int) $maxLength }},
         placeholder: @js($placeholder),
         wireProperty: @js($wireProperty),
         saveMethod: @js($saveMethod),
     })"
     @click.outside="colorPickerOpen = false">
    <label class="form-label" id="{{ $labelId }}">{{ $label }}</label>

    <div class="vl-rich-editor" wire:ignore>
        <div class="vl-rich-editor-toolbar" role="toolbar" aria-label="{{ $toolbarAriaLabel }}">
            <button type="button" class="vl-rich-editor-btn" title="Negrita"
                    @mousedown.prevent="aplicar('bold')"><span class="font-bold">B</span></button>
            <button type="button" class="vl-rich-editor-btn italic" title="Cursiva"
                    @mousedown.prevent="aplicar('italic')"><span class="italic">I</span></button>
            <button type="button" class="vl-rich-editor-btn" title="Subrayado"
                    @mousedown.prevent="aplicar('underline')"><span class="underline">U</span></button>
            <span class="vl-rich-editor-sep" aria-hidden="true"></span>
            <div class="relative">
                <button type="button" class="vl-rich-editor-btn" title="Color de texto"
                        @mousedown.prevent="colorPickerOpen = !colorPickerOpen">
                    <span class="flex flex-col items-center leading-none">
                        <span class="text-[11px] font-bold">A</span>
                        <span class="mt-0.5 h-0.5 w-3 rounded bg-red-600"></span>
                    </span>
                </button>
                <div x-show="colorPickerOpen" x-cloak
                     class="vl-rich-editor-colors"
                     @mousedown.prevent>
                    <template x-for="color in colors" :key="color">
                        <button type="button"
                                class="vl-rich-editor-color"
                                :style="`background-color: ${color}`"
                                :title="color"
                                @mousedown.prevent="aplicar('foreColor', color)"></button>
                    </template>
                </div>
            </div>
            <button type="button" class="vl-rich-editor-btn" title="Quitar formato"
                    @mousedown.prevent="aplicar('removeFormat')">✕</button>
        </div>
        <div x-ref="editor"
             class="vl-rich-editor-surface {{ $surfaceClass }}"
             contenteditable="true"
             role="textbox"
             aria-multiline="true"
             aria-labelledby="{{ $labelId }}"
             data-placeholder="{{ $placeholder }}"
             @input="actualizarContador()"
             @keydown.escape.stop></div>
    </div>

    @if ($showCounter)
        <p class="mt-1 text-[11px]"
           :class="htmlLength > maxLength ? 'text-red-600' : 'text-neutral-500'">
            <span x-text="htmlLength"></span> / <span x-text="maxLength"></span> caracteres (HTML).
        </p>
    @endif
    @error($errorBag) <p class="form-error">{{ $message }}</p> @enderror

    {{ $slot }}
</div>
