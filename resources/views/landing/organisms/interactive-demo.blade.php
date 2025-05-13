@props([
    'title' => __('landing.interactive_demo_title'),
    'description' => __('landing.interactive_demo_description')
])

<section {{ $attributes->merge(['class' => 'py-20 bg-white dark:bg-gray-900']) }}>
    <div class="container mx-auto px-4">
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
                        <div class="ml-4 text-sm text-gray-500 dark:text-gray-400 font-medium">EnkiFlow Timer</div>
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
                            <div class="text-6xl font-mono mb-8 text-gray-900 dark:text-gray-100">
                                <span id="hours">00</span>:<span id="minutes">00</span>:<span id="seconds">00</span>
                            </div>
                            
                            <div class="flex gap-4">
                                <button id="startButton" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors shadow-md">
                                    {{ __('landing.start') }}
                                </button>
                                
                                <button id="resetButton" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-medium rounded-lg transition-colors">
                                    {{ __('landing.reset') }}
                                </button>
                            </div>
                            
                            <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg w-full">
                                <h3 class="font-medium text-gray-900 dark:text-white mb-2">{{ __('landing.recent_tasks') }}</h3>
                                <p class="text-gray-500 dark:text-gray-400 text-sm italic">{{ __('landing.no_tasks_recorded') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- App Activity Sidebar -->
                <div class="bg-white dark:bg-gray-800 rounded-xl overflow-hidden shadow-xl border border-gray-100 dark:border-gray-700">
                    <div class="h-10 bg-gray-100 dark:bg-gray-700 flex items-center px-4">
                        <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                        <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                        <div class="ml-4 text-sm text-gray-500 dark:text-gray-400 font-medium">App Activity</div>
                    </div>
                    
                    <div class="p-4">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-3">Current Day Activity</h3>
                        
                        <!-- Time Distribution Chart -->
                        <div class="mb-6">
                            <div class="mb-2 text-sm text-gray-500 dark:text-gray-400">Time Usage by Category</div>
                            <div class="grid grid-cols-12 h-5 rounded-md overflow-hidden">
                                <div class="bg-blue-500" style="grid-column: span 5;"></div>
                                <div class="bg-green-500" style="grid-column: span 3;"></div>
                                <div class="bg-purple-500" style="grid-column: span 2;"></div>
                                <div class="bg-yellow-500" style="grid-column: span 1;"></div>
                                <div class="bg-gray-400" style="grid-column: span 1;"></div>
                            </div>
                            <div class="flex flex-wrap mt-2 text-xs gap-x-3">
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full mr-1"></div>
                                    <span class="text-gray-600 dark:text-gray-300">Development</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                                    <span class="text-gray-600 dark:text-gray-300">Meetings</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-purple-500 rounded-full mr-1"></div>
                                    <span class="text-gray-600 dark:text-gray-300">Email</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-yellow-500 rounded-full mr-1"></div>
                                    <span class="text-gray-600 dark:text-gray-300">Research</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full mr-1"></div>
                                    <span class="text-gray-600 dark:text-gray-300">Other</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Application Usage -->
                        <div>
                            <div class="mb-2 text-sm text-gray-500 dark:text-gray-400">Most Used Applications</div>
                            <div class="space-y-3">
                                <!-- VS Code -->
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-800 dark:text-gray-200">VS Code</div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full">
                                            <div class="bg-blue-500 h-2 rounded-full" style="width: 70%;"></div>
                                        </div>
                                    </div>
                                    <div class="ml-2 text-xs text-gray-500 dark:text-gray-400">2h 25m</div>
                                </div>
                                
                                <!-- Chrome -->
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-800 dark:text-gray-200">Chrome</div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full">
                                            <div class="bg-green-500 h-2 rounded-full" style="width: 50%;"></div>
                                        </div>
                                    </div>
                                    <div class="ml-2 text-xs text-gray-500 dark:text-gray-400">1h 30m</div>
                                </div>
                                
                                <!-- Slack -->
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-purple-500 rounded-full mr-2"></div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-800 dark:text-gray-200">Slack</div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full">
                                            <div class="bg-purple-500 h-2 rounded-full" style="width: 35%;"></div>
                                        </div>
                                    </div>
                                    <div class="ml-2 text-xs text-gray-500 dark:text-gray-400">45m</div>
                                </div>
                                
                                <!-- Zoom -->
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-800 dark:text-gray-200">Zoom</div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full">
                                            <div class="bg-yellow-500 h-2 rounded-full" style="width: 30%;"></div>
                                        </div>
                                    </div>
                                    <div class="ml-2 text-xs text-gray-500 dark:text-gray-400">30m</div>
                                </div>
                                
                                <!-- Outlook -->
                                <div class="flex items-center">
                                    <div class="w-2 h-2 bg-red-500 rounded-full mr-2"></div>
                                    <div class="flex-1">
                                        <div class="text-sm text-gray-800 dark:text-gray-200">Outlook</div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full">
                                            <div class="bg-red-500 h-2 rounded-full" style="width: 20%;"></div>
                                        </div>
                                    </div>
                                    <div class="ml-2 text-xs text-gray-500 dark:text-gray-400">20m</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Timeline Visualization -->
                        <div class="mt-6">
                            <div class="mb-2 text-sm text-gray-500 dark:text-gray-400">Today's Timeline</div>
                            <div class="h-10 bg-gray-100 dark:bg-gray-700 rounded-md overflow-hidden flex">
                                <div class="h-full bg-blue-500" style="width: 20%;"></div>
                                <div class="h-full bg-green-500" style="width: 15%;"></div>
                                <div class="h-full bg-purple-500" style="width: 10%;"></div>
                                <div class="h-full bg-yellow-500" style="width: 25%;"></div>
                                <div class="h-full bg-red-500" style="width: 5%;"></div>
                                <div class="h-full bg-blue-500" style="width: 15%;"></div>
                                <div class="h-full bg-green-500" style="width: 10%;"></div>
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
                <p class="text-gray-600 dark:text-gray-400">
                    {{ __('landing.demo_explanation') }}
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Add enhanced timer script -->
<script src="{{ asset('js/landing/enhanced-timer.js') }}"></script>
