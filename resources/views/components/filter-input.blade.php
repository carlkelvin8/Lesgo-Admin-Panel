@props(['name' => '', 'label' => '', 'type' => 'text', 'placeholder' => '', 'options' => [], 'value' => '', 'allOption' => true])

@php($inputId = 'filter-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', $name))

<div @class(['flex-1 min-w-[200px]' => $type !== 'select'])>
    <label for="{{ $inputId }}" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $label }}</label>
    @if($type === 'select')
        <select id="{{ $inputId }}" name="{{ $name }}" class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 focus:border-blue-500 outline-none transition w-full">
            @if($allOption && !array_key_exists('', $options))<option value="">All</option>@endif
            @foreach($options as $key => $option)
                <option value="{{ $key }}" {{ request($name, $value) == $key ? 'selected' : '' }}>{{ $option }}</option>
            @endforeach
        </select>
    @elseif($type === 'date')
        <input id="{{ $inputId }}" type="date" name="{{ $name }}" value="{{ request($name, $value) }}" class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 focus:border-blue-500 outline-none transition w-full">
    @else
        <input id="{{ $inputId }}" type="text" name="{{ $name }}" value="{{ request($name, $value) }}" placeholder="{{ $placeholder }}" class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 outline-none transition w-full">
    @endif
</div>
