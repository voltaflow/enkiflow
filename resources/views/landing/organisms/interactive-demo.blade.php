@props([
    'title' => __('landing.interactive_demo_title'),
    'description' => __('landing.interactive_demo_description')
])

<section {{ $attributes->merge(['class' => 'py-20 bg-white dark:bg-gray-900']) }}>
    <div class="container mx-auto px-4">
        <div class="flex justify-end mb-2">
            <!-- Dark mode toggle -->
            <button id="theme-toggle" class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5">
                <svg id="theme-toggle-dark-icon" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                <svg id="theme-toggle-light-icon" class="w-5 h-5 hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" fill-rule="evenodd" clip-rule="evenodd"></path></svg>
            </button>
            
            <!-- Language toggle -->
            <div class="ml-4 flex border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <a href="/set-locale/en" class="px-3 py-1 text-sm {{ app()->getLocale() === 'en' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' }}">EN</a>
                <a href="/set-locale/es" class="px-3 py-1 text-sm {{ app()->getLocale() === 'es' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200' }}">ES</a>
            </div>
        </div>
        
        <div class="text-center mb-16 max-w-3xl mx-auto">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                {{ $title }}
            </h2>
            <p class="text-xl text-gray-600 dark:text-gray-300">
                {{ $description }}
            </p>
        </div>
        
        <div class="max-w-5xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Timer Component -->
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="h-10 bg-gray-100 dark:bg-gray-700 flex items-center px-4">
                        <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                        <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <div class="ml-4 text-sm text-gray-500 dark:text-gray-400 font-medium">{{ __('landing.enkiflow_timer') }}</div>
                    </div>
                    
                    <div class="p-8">
                        <div class="mb-6">
                            <label for="taskDescription" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ __('landing.task_description') }}
                            </label>
                            <input 
                                type="text" 
                                id="taskDescription" 
                                placeholder="{{ __('landing.task_placeholder') }}"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400"
                            >
                        </div>
                        
                        <div id="solidJsCounter" class="flex flex-col items-center justify-center">
                            <div class="text-7xl font-mono mb-8 text-gray-900 dark:text-gray-100 tracking-wider">
                                <span id="hours">00</span>:<span id="minutes">00</span>:<span id="seconds">00</span>
                            </div>
                            
                            <div class="flex gap-6">
                                <button id="startButton" class="relative px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-all duration-300 shadow-md hover:translate-y-[-2px] hover:shadow-lg group">
                                    <span class="absolute inset-0 rounded-lg bg-blue-500 opacity-0 group-hover:opacity-20 animate-pulse"></span>
                                    <div class="flex items-center gap-2">
                                        <span>{{ __('landing.start') }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                                    </div>
                                </button>
                                
                                <button id="resetButton" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium rounded-lg transition-all duration-300 hover:translate-y-[-2px] hover:shadow-md">
                                    {{ __('landing.reset') }}
                                </button>
                            </div>
                            
                            <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg w-full transition-all duration-300">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ __('landing.recent_tasks') }}</h3>
                                    
                                    <!-- Task streak - per your gamification suggestion -->
                                    <div class="flex items-center gap-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-xs px-2 py-1 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                                        <span>2-day streak</span>
                                    </div>
                                </div>
                                
                                <div class="task-list empty-state">
                                    <p class="text-gray-500 dark:text-gray-400 text-sm italic">{{ __('landing.no_tasks_recorded') }}</p>
                                </div>
                                
                                <div class="task-list with-tasks hidden">
                                    <!-- Sample task item (initially hidden, would be shown via JS when timer starts) -->
                                    <div class="task-item p-2 my-2 bg-white dark:bg-gray-800 rounded-md border border-gray-100 dark:border-gray-700 hover:shadow-md transition-all duration-300 hover:translate-y-[-2px] flex justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                            <span class="text-gray-800 dark:text-gray-200 text-sm">Design homepage</span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">25m</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- App Activity Sidebar -->
                <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-xl border border-gray-100 dark:border-gray-700 transition-all duration-300">
                    <div class="h-10 bg-gray-100 dark:bg-gray-700 flex items-center justify-between px-4">
                        <div class="flex items-center">
                            <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                            <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <div class="ml-4 text-sm text-gray-500 dark:text-gray-400 font-medium">{{ __('landing.app_activity') }}</div>
                        </div>
                        
                        <!-- Start Free Trial Button (per your CTA suggestion) -->
                        <a href="/register" class="hidden md:flex items-center text-xs text-white bg-blue-600 hover:bg-blue-700 px-2 py-1 rounded transition-all duration-300" id="trialButton">
                            <span>Start Free Trial</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </a>
                    </div>
                    
                    <div class="p-4">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-3 flex items-center">
                            {{ __('landing.current_day_activity') }}
                            
                            <!-- Pomodoro indicator -->
                            <div class="ml-auto flex items-center bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-xs px-2 py-1 rounded-full">
                                <svg viewBox="0 0 24 24" class="w-3 h-3 mr-1 text-blue-500" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                                <span>25:00</span>
                            </div>
                        </h3>
                        
                        <!-- Category Filter Tabs -->
                        <div class="flex gap-2 mb-4 overflow-x-auto pb-2 scrollbar-hide">
                            <button class="flex items-center bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 text-xs px-2 py-1 rounded-md">
                                <span>All</span>
                            </button>
                            <button class="flex items-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs px-2 py-1 rounded-md hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                <span>{{ __('landing.development') }}</span>
                            </button>
                            <button class="flex items-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs px-2 py-1 rounded-md hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                <span>{{ __('landing.meetings') }}</span>
                            </button>
                            <button class="flex items-center bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs px-2 py-1 rounded-md hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                <span>{{ __('landing.other') }}</span>
                            </button>
                        </div>
                        
                        <!-- Time Distribution Chart -->
                        <div class="mb-6 group/chart">
                            <div class="mb-2 text-sm text-gray-500 dark:text-gray-400 flex justify-between items-center">
                                <div>{{ __('landing.time_usage_by_category') }}</div>
                                <div class="text-xs text-blue-600 dark:text-blue-400">5h 30m</div>
                            </div>
                            <div class="grid grid-cols-12 h-5 rounded-md overflow-hidden relative">
                                <div class="bg-blue-600 group-hover/chart:bg-blue-500 transition-colors duration-300" style="grid-column: span 5;"></div>
                                <div class="bg-green-600 group-hover/chart:bg-green-500 transition-colors duration-300" style="grid-column: span 3;"></div>
                                <div class="bg-purple-600 group-hover/chart:bg-purple-500 transition-colors duration-300" style="grid-column: span 2;"></div>
                                <div class="bg-yellow-600 group-hover/chart:bg-yellow-500 transition-colors duration-300" style="grid-column: span 1;"></div>
                                <div class="bg-gray-400 group-hover/chart:bg-gray-500 transition-colors duration-300" style="grid-column: span 1;"></div>
                                
                                <!-- Tooltip (hidden by default, shown on hover via JS) -->
                                <div id="chart-tooltip" class="absolute top-0 left-0 bg-white dark:bg-gray-800 shadow-lg rounded px-2 py-1 text-xs border border-gray-200 dark:border-gray-700 hidden pointer-events-none transform -translate-y-full">
                                    <div class="font-semibold">{{ __('landing.development') }}</div>
                                    <div>2h 45m (45%)</div>
                                </div>
                            </div>
                            <div class="flex flex-wrap mt-2 text-xs gap-x-3">
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-blue-600 rounded-full mr-1"></div>
                                    <span class="text-gray-600 dark:text-gray-300">{{ __('landing.development') }}</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-green-600 rounded-full mr-1"></div>
                                    <span class="text-gray-600 dark:text-gray-300">{{ __('landing.meetings') }}</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-purple-600 rounded-full mr-1"></div>
                                    <span class="text-gray-600 dark:text-gray-300">{{ __('landing.email') }}</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-yellow-600 rounded-full mr-1"></div>
                                    <span class="text-gray-600 dark:text-gray-300">{{ __('landing.research') }}</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full mr-1"></div>
                                    <span class="text-gray-600 dark:text-gray-300">{{ __('landing.other') }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Application Usage -->
                        <div>
                            <div class="mb-2 text-sm text-gray-500 dark:text-gray-400">{{ __('landing.most_used_applications') }}</div>
                            <div class="space-y-3">
                                <!-- VS Code -->
                                <div class="app-item flex items-center group hover:bg-gray-50 dark:hover:bg-gray-800/80 rounded-md p-1 transition-all duration-300 cursor-pointer">
                                    <div class="w-2 h-2 bg-blue-600 rounded-full mr-2 flex-shrink-0"></div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-800 dark:text-gray-200">VS Code</div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                                            <div class="bg-blue-600 h-2 rounded-full group-hover:bg-blue-500 transition-all duration-300" style="width: 70%;"></div>
                                        </div>
                                    </div>
                                    <div class="ml-2 text-xs text-gray-500 dark:text-gray-400">2h 25m</div>
                                </div>
                                
                                <!-- Chrome -->
                                <div class="app-item flex items-center group hover:bg-gray-50 dark:hover:bg-gray-800/80 rounded-md p-1 transition-all duration-300 cursor-pointer">
                                    <div class="w-2 h-2 bg-green-600 rounded-full mr-2 flex-shrink-0"></div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-800 dark:text-gray-200">Chrome</div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                                            <div class="bg-green-600 h-2 rounded-full group-hover:bg-green-500 transition-all duration-300" style="width: 50%;"></div>
                                        </div>
                                    </div>
                                    <div class="ml-2 text-xs text-gray-500 dark:text-gray-400">1h 30m</div>
                                </div>
                                
                                <!-- Slack -->
                                <div class="app-item flex items-center group hover:bg-gray-50 dark:hover:bg-gray-800/80 rounded-md p-1 transition-all duration-300 cursor-pointer">
                                    <div class="w-2 h-2 bg-purple-600 rounded-full mr-2 flex-shrink-0"></div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-800 dark:text-gray-200">Slack</div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                                            <div class="bg-purple-600 h-2 rounded-full group-hover:bg-purple-500 transition-all duration-300" style="width: 35%;"></div>
                                        </div>
                                    </div>
                                    <div class="ml-2 text-xs text-gray-500 dark:text-gray-400">45m</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Timeline Visualization -->
                        <div class="mt-6">
                            <div class="mb-2 text-sm text-gray-500 dark:text-gray-400 flex justify-between items-center">
                                <div>{{ __('landing.todays_timeline') }}</div>
                                <div class="text-xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                        <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                                        active
                                    </span>
                                </div>
                            </div>
                            <div class="h-10 bg-gray-100 dark:bg-gray-700 rounded-md overflow-hidden flex">
                                <div class="h-full bg-blue-600" style="width: 20%;"></div>
                                <div class="h-full bg-green-600" style="width: 15%;"></div>
                                <div class="h-full bg-purple-600" style="width: 10%;"></div>
                                <div class="h-full bg-yellow-600" style="width: 25%;"></div>
                                <div class="h-full bg-red-500" style="width: 5%;"></div>
                                <div class="h-full bg-blue-600" style="width: 15%;"></div>
                                <div class="h-full bg-green-600 animate-pulse" style="width: 10%;"></div>
                            </div>
                            <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
                                <span>9AM</span>
                                <span>12PM</span>
                                <span>3PM</span>
                                <span>6PM</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <div class="inline-flex items-center px-4 py-2 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    <p class="text-sm">
                        {{ __('landing.demo_explanation') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Enhanced timer and UI scripts -->
<script src="{{ asset('js/landing/enhanced-timer.js') }}"></script>
<script>
    window.demoTranslations = {
        recent_tasks: "{{ __('landing.recent_tasks') }}",
        no_tasks_recorded: "{{ __('landing.no_tasks_recorded') }}"
    };
    window.demoTasks = @json(__('landing.demo_tasks'));
</script>

<script>
    // Theme toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Get theme toggle button
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        // On page load, set the initial icon state
        if (localStorage.getItem('color-theme') === 'dark' || 
            (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            themeToggleLightIcon.classList.remove('hidden');
            document.documentElement.classList.add('dark');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
            document.documentElement.classList.remove('dark');
        }

        // Toggle theme on button click
        themeToggleBtn.addEventListener('click', function() {
            // Toggle icons
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');
            
            // Toggle theme
            if (localStorage.getItem('color-theme') === 'dark') {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        });

        // Custom pulse animation for buttons
        const startButton = document.getElementById('startButton');
        const startButtonPulse = startButton.querySelector('.absolute');
        const trialButton = document.getElementById('trialButton');
        const resetButton = document.getElementById('resetButton');
        const emptyState = document.querySelector('.task-list.empty-state');
        const withTasks = document.querySelector('.task-list.with-tasks');
        
        // Initial quick pulse animation (3 times)
        let pulseCount = 0;
        const quickPulseInterval = setInterval(() => {
            if (pulseCount < 3) {
                startButtonPulse.classList.add('opacity-20');
                setTimeout(() => {
                    startButtonPulse.classList.remove('opacity-20');
                }, 500);
                pulseCount++;
            } else {
                clearInterval(quickPulseInterval);
                // Switch to slow pulse
                setInterval(() => {
                    startButtonPulse.classList.add('opacity-20');
                    setTimeout(() => {
                        startButtonPulse.classList.remove('opacity-20');
                    }, 1000);
                }, 6000); // Pulse every 6 seconds
            }
        }, 1000);
        
        // Add pulsing effect to the trial button (3 quick pulses, then slow)
        if (trialButton) {
            // Initial quick pulses (3 times)
            let trialPulseCount = 0;
            const trialQuickPulseInterval = setInterval(() => {
                if (trialPulseCount < 3) {
                    trialButton.classList.add('opacity-70');
                    setTimeout(() => {
                        trialButton.classList.remove('opacity-70');
                    }, 500);
                    trialPulseCount++;
                } else {
                    clearInterval(trialQuickPulseInterval);
                    // Switch to slow pulse
                    setInterval(() => {
                        trialButton.classList.add('opacity-70');
                        setTimeout(() => {
                            trialButton.classList.remove('opacity-70');
                        }, 1000);
                    }, 8000); // Pulse every 8 seconds (even slower than the start button)
                }
            }, 1000);
        }
        
        // Enhanced timer UI behavior
        startButton.addEventListener('click', function() {
            // Hide empty state and show tasks after a short delay
            setTimeout(() => {
                if (emptyState && withTasks) {
                    emptyState.classList.add('hidden');
                    withTasks.classList.remove('hidden');
                }
            }, 800);
        });

        resetButton.addEventListener('click', function() {
            // Show empty state and hide tasks
            if (emptyState && withTasks) {
                emptyState.classList.remove('hidden');
                withTasks.classList.add('hidden');
            }
        });
        
        // Initialize tooltip behavior for chart elements
        const chartBars = document.querySelectorAll('.group\\/chart [class^="bg-"]');
        const chartTooltip = document.getElementById('chart-tooltip');
        
        if (chartBars.length && chartTooltip) {
            chartBars.forEach(bar => {
                bar.addEventListener('mouseenter', function(e) {
                    // Get category from bar's color class and position tooltip
                    const colorClass = Array.from(this.classList).find(cls => cls.startsWith('bg-'));
                    let category = 'Development';
                    let percentage = '45%';
                    let time = '2h 45m';
                    
                    if (colorClass.includes('green')) {
                        category = 'Meetings';
                        percentage = '27%';
                        time = '1h 30m';
                    } else if (colorClass.includes('purple')) {
                        category = 'Email';
                        percentage = '18%';
                        time = '1h';
                    } else if (colorClass.includes('yellow')) {
                        category = 'Research';
                        percentage = '9%';
                        time = '30m';
                    } else if (colorClass.includes('gray')) {
                        category = 'Other';
                        percentage = '1%';
                        time = '5m';
                    }
                    
                    // Update tooltip content and position
                    chartTooltip.querySelector('.font-semibold').textContent = category;
                    chartTooltip.querySelector('div:not(.font-semibold)').textContent = time + ' (' + percentage + ')';
                    
                    const rect = this.getBoundingClientRect();
                    const tooltipRect = chartTooltip.getBoundingClientRect();
                    const chartRect = document.querySelector('.group\\/chart').getBoundingClientRect();
                    
                    // Position tooltip centered above bar
                    chartTooltip.style.left = (rect.left + rect.width/2 - tooltipRect.width/2 - chartRect.left) + 'px';
                    chartTooltip.style.top = '-28px';
                    chartTooltip.classList.remove('hidden');
                });
                
                bar.addEventListener('mouseleave', function() {
                    chartTooltip.classList.add('hidden');
                });
            });
        }
    });
</script>
