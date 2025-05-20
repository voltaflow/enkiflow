<div class="language-switcher flex border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
    <a href="{{ route('set-locale', 'en') }}" class="px-3 py-1 text-sm {{ app()->getLocale() === 'en' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700' }} transition-colors duration-200">
        EN
    </a>
    <a href="{{ route('set-locale', 'es') }}" class="px-3 py-1 text-sm {{ app()->getLocale() === 'es' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700' }} transition-colors duration-200">
        ES
    </a>
</div>