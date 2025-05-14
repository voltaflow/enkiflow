<div class="fixed bottom-0 left-0 p-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-tr-lg shadow-lg text-xs z-50">
    <div class="mb-1 font-medium text-gray-700 dark:text-gray-300">Appearance Debug Info:</div>
    <div class="text-gray-600 dark:text-gray-400">
        <p><span class="font-medium">Current Theme:</span> <span id="debug-current-theme">{{ session('appearance', 'system') }}</span></p>
        <p><span class="font-medium">Session Theme:</span> {{ session('appearance', 'Not set') }}</p>
        <p><span class="font-medium">Cookie Theme:</span> <span id="debug-cookie-theme">Not checked</span></p>
        <p><span class="font-medium">localStorage Theme:</span> <span id="debug-local-storage">Not checked</span></p>
        <p><span class="font-medium">System Preference:</span> <span id="debug-system-pref">Not checked</span></p>
        <p><span class="font-medium">HTML Class:</span> <span id="debug-html-class">Not checked</span></p>
    </div>
</div>

<script>
    // Update debug info on load
    document.addEventListener('DOMContentLoaded', function() {
        // LocalStorage
        const localTheme = localStorage.getItem('theme') || 'Not set';
        document.getElementById('debug-local-storage').textContent = localTheme;
        
        // Cookie
        const cookieTheme = document.cookie.split('; ')
            .find(row => row.startsWith('appearance='))
            ?.split('=')[1] || 'Not set';
        document.getElementById('debug-cookie-theme').textContent = cookieTheme;
        
        // System preference
        const systemPref = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.getElementById('debug-system-pref').textContent = systemPref;
        
        // HTML class
        const htmlClass = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        document.getElementById('debug-html-class').textContent = htmlClass;
        
        // Watch for theme changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    const htmlClass = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
                    document.getElementById('debug-html-class').textContent = htmlClass;
                }
            });
        });
        
        observer.observe(document.documentElement, { attributes: true });
    });
</script>