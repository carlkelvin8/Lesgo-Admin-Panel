@props(['name' => '', 'label' => '', 'type' => 'text', 'placeholder' => '', 'options' => [], 'value' => ''])

<div @class(['flex-1 min-w-[200px]' => $type !== 'select'])>
    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $label }}</label>
    @if($type === 'select')
        <select name="{{ $name }}" class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 focus:border-blue-500 outline-none transition w-full">
            @foreach($options as $key => $option)
                <option value="{{ $key }}" {{ request($name, $value) == $key ? 'selected' : '' }}>{{ $option }}</option>
            @endforeach
        </select>
    @elseif($type === 'date')
        <input type="date" name="{{ $name }}" value="{{ request($name, $value) }}" class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 focus:border-blue-500 outline-none transition w-full">
    @else
        <input type="text" name="{{ $name }}" value="{{ request($name, $value) }}" placeholder="{{ $placeholder }}" class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 text-sm bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:border-blue-500 outline-none transition w-full">
    @endif
</div>
