<div class="fixed bottom-0 right-0 p-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-tl-lg shadow-lg text-xs z-50">
    <div class="mb-1 font-medium text-gray-700 dark:text-gray-300">Language Debug Info:</div>
    <div class="text-gray-600 dark:text-gray-400">
        <p><span class="font-medium">Current Locale:</span> {{ app()->getLocale() }}</p>
        <p><span class="font-medium">Session Locale:</span> {{ session('locale', 'Not set') }}</p>
        <p><span class="font-medium">Cookie Locale:</span> {{ request()->cookie('locale', 'Not set') }}</p>
        <p><span class="font-medium">Fallback Locale:</span> {{ config('app.fallback_locale') }}</p>
        <p><span class="font-medium">Default Locale:</span> {{ config('app.locale') }}</p>
    </div>
</div>