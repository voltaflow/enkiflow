// EnkiFlow Enhanced Timer with Pomodoro functionality
document.addEventListener('DOMContentLoaded', function() {
    // Main elements
    const timerContainer = document.getElementById('solidJsCounter');
    if (!timerContainer) return;
    
    const hoursElement = document.getElementById('hours');
    const minutesElement = document.getElementById('minutes');
    const secondsElement = document.getElementById('seconds');
    const taskInput = document.getElementById('taskDescription');
    const startButton = document.getElementById('startButton');
    const resetButton = document.getElementById('resetButton');
    const recentTasksContainer = document.querySelector('#solidJsCounter .mt-8');
    
    // Timer state
    let seconds = 0;
    let minutes = 0;
    let hours = 0;
    let timerInterval = null;
    let timerRunning = false;
    let pomodoroMode = false;
    let currentCycle = 'work'; // 'work' or 'break'
    let pomodoroConfig = {
        workDuration: 25 * 60, // 25 minutes in seconds
        shortBreakDuration: 5 * 60, // 5 minutes in seconds
        longBreakDuration: 15 * 60, // 15 minutes in seconds
        cyclesBeforeLongBreak: 4,
        currentPomodoros: 0
    };
    
    // Task history (simulated)
    const recentTasks = [];
    
    // Application usage history (simulated)
    const appUsageHistory = [
        { name: 'Visual Studio Code', duration: 145, category: 'Development', color: '#0078d7' },
        { name: 'Chrome', duration: 87, category: 'Browsing', color: '#4285F4' },
        { name: 'Slack', duration: 35, category: 'Communication', color: '#611f69' },
        { name: 'Zoom', duration: 62, category: 'Meetings', color: '#0b5cff' },
        { name: 'Outlook', duration: 29, category: 'Email', color: '#0078d4' }
    ];
    
    // Format time to display as 00:00:00
    function formatTimeDisplay() {
        hoursElement.textContent = hours.toString().padStart(2, '0');
        minutesElement.textContent = minutes.toString().padStart(2, '0');
        secondsElement.textContent = seconds.toString().padStart(2, '0');
    }
    
    // Update timer
    function updateTimer() {
        seconds++;
        if (seconds >= 60) {
            seconds = 0;
            minutes++;
            if (minutes >= 60) {
                minutes = 0;
                hours++;
            }
        }
        
        formatTimeDisplay();
        
        // Check if pomodoro cycle is complete
        if (pomodoroMode) {
            const totalSeconds = hours * 3600 + minutes * 60 + seconds;
            const workDuration = pomodoroConfig.workDuration;
            const shortBreak = pomodoroConfig.shortBreakDuration;
            const longBreak = pomodoroConfig.longBreakDuration;
            
            if (currentCycle === 'work' && totalSeconds >= workDuration) {
                pomodoroConfig.currentPomodoros++;
                pauseTimer();
                
                if (pomodoroConfig.currentPomodoros % pomodoroConfig.cyclesBeforeLongBreak === 0) {
                    // Time for a long break
                    showNotification('Time for a long break!', 'Take 15 minutes to relax completely.');
                    currentCycle = 'longBreak';
                    resetTimerValues();
                } else {
                    // Time for a short break
                    showNotification('Time for a short break!', 'Take a 5-minute breather.');
                    currentCycle = 'shortBreak';
                    resetTimerValues();
                }
            } else if (currentCycle === 'shortBreak' && totalSeconds >= shortBreak) {
                // Break is over, back to work
                pauseTimer();
                showNotification('Break is over!', 'Time to get back to work.');
                currentCycle = 'work';
                resetTimerValues();
            } else if (currentCycle === 'longBreak' && totalSeconds >= longBreak) {
                // Long break is over, back to work
                pauseTimer();
                showNotification('Long break is over!', 'Time to get back to work.');
                currentCycle = 'work';
                resetTimerValues();
            }
        }
    }
    
    // Start timer
    function startTimer() {
        if (timerRunning) return;
        
        // Get task description
        const taskDescription = taskInput.value.trim();
        if (!taskDescription && !pomodoroMode) {
            // Only require description if not in the middle of a Pomodoro cycle
            alert('Please enter a task description');
            return;
        }
        
        // Update UI
        timerRunning = true;
        startButton.textContent = 'Pause';
        startButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        startButton.classList.add('bg-yellow-600', 'hover:bg-yellow-700');
        
        // Start counting
        timerInterval = setInterval(updateTimer, 1000);
    }
    
    // Pause timer
    function pauseTimer() {
        if (!timerRunning) return;
        
        // Clear interval
        clearInterval(timerInterval);
        
        // Update UI
        timerRunning = false;
        startButton.textContent = 'Resume';
        startButton.classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
        startButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
    }
    
    // Reset timer
    function resetTimer() {
        // Clear interval and reset values
        pauseTimer();
        resetTimerValues();
        
        // Update UI
        startButton.textContent = 'Start';
        
        // If not continuing a Pomodoro cycle, add the task to history
        if (!pomodoroMode || currentCycle === 'work') {
            const taskDescription = taskInput.value.trim();
            if (taskDescription && (hours > 0 || minutes > 0 || seconds > 0)) {
                addTaskToHistory(taskDescription);
            }
            
            // Clear input
            taskInput.value = '';
        }
        
        // Reset Pomodoro cycle if requested
        if (pomodoroMode && confirm('Do you want to reset the Pomodoro cycle?')) {
            pomodoroMode = false;
            currentCycle = 'work';
            pomodoroConfig.currentPomodoros = 0;
        }
    }
    
    // Reset timer values without affecting other states
    function resetTimerValues() {
        seconds = 0;
        minutes = 0;
        hours = 0;
        formatTimeDisplay();
    }
    
    // Add a completed task to history
    function addTaskToHistory(description) {
        // Create a new task entry
        const totalSeconds = hours * 3600 + minutes * 60 + seconds;
        const task = {
            description: description,
            duration: formatDuration(totalSeconds),
            timestamp: new Date(),
            totalSeconds: totalSeconds
        };
        
        // Add to the list
        recentTasks.unshift(task);
        
        // Limit to 5 most recent tasks
        if (recentTasks.length > 5) {
            recentTasks.pop();
        }
        
        // Update UI
        updateTaskHistory();
    }
    
    // Format duration from seconds to readable format
    function formatDuration(totalSeconds) {
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        
        let result = '';
        if (hours > 0) {
            result += `${hours}h `;
        }
        if (minutes > 0 || hours > 0) {
            result += `${minutes}m `;
        }
        result += `${seconds}s`;
        
        return result;
    }
    
    // Update the task history display
    function updateTaskHistory() {
        // Clear current content
        recentTasksContainer.innerHTML = '';
        
        // Add heading
        const heading = document.createElement('h3');
        heading.className = 'font-medium text-gray-900 dark:text-white mb-2';
        heading.textContent = 'Recent Tasks';
        recentTasksContainer.appendChild(heading);
        
        // If no tasks, show placeholder
        if (recentTasks.length === 0) {
            const noTasks = document.createElement('p');
            noTasks.className = 'text-gray-500 dark:text-gray-400 text-sm italic';
            noTasks.textContent = 'No tasks recorded yet. Start the timer to track your first task!';
            recentTasksContainer.appendChild(noTasks);
            return;
        }
        
        // Create task list
        const taskList = document.createElement('div');
        taskList.className = 'space-y-2';
        
        for (const task of recentTasks) {
            const taskItem = document.createElement('div');
            taskItem.className = 'flex justify-between items-start p-2 border-b border-gray-100 dark:border-gray-700 last:border-0';
            
            // Task info
            const taskInfo = document.createElement('div');
            
            const taskDescription = document.createElement('div');
            taskDescription.className = 'font-medium text-gray-900 dark:text-white';
            taskDescription.textContent = task.description;
            
            const taskTimestamp = document.createElement('div');
            taskTimestamp.className = 'text-sm text-gray-500 dark:text-gray-400';
            taskTimestamp.textContent = formatTimestamp(task.timestamp);
            
            taskInfo.appendChild(taskDescription);
            taskInfo.appendChild(taskTimestamp);
            
            // Task duration
            const taskDuration = document.createElement('div');
            taskDuration.className = 'font-mono text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-gray-700 dark:text-gray-300';
            taskDuration.textContent = task.duration;
            
            // Add to item
            taskItem.appendChild(taskInfo);
            taskItem.appendChild(taskDuration);
            
            // Add to list
            taskList.appendChild(taskItem);
        }
        
        recentTasksContainer.appendChild(taskList);
        
        // Add app usage tab
        addAppUsageTab();
    }
    
    // Add app usage tab to the task history
    function addAppUsageTab() {
        // Create tabs container
        const tabsContainer = document.createElement('div');
        tabsContainer.className = 'mt-4 border-t border-gray-100 dark:border-gray-700 pt-4';
        
        // Create tabs header
        const tabsHeader = document.createElement('div');
        tabsHeader.className = 'flex border-b border-gray-200 dark:border-gray-700';
        
        // Tasks tab
        const tasksTab = document.createElement('button');
        tasksTab.className = 'px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 border-b-2 border-blue-600 dark:border-blue-400';
        tasksTab.textContent = 'Tasks';
        tasksTab.dataset.tab = 'tasks';
        
        // App Usage tab
        const appUsageTab = document.createElement('button');
        appUsageTab.className = 'px-4 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300';
        appUsageTab.textContent = 'App Usage';
        appUsageTab.dataset.tab = 'app-usage';
        
        // Add tabs to header
        tabsHeader.appendChild(tasksTab);
        tabsHeader.appendChild(appUsageTab);
        
        // Create app usage content (hidden by default)
        const appUsageContent = document.createElement('div');
        appUsageContent.className = 'mt-4 hidden';
        appUsageContent.id = 'app-usage-content';
        
        // Add app usage chart placeholder
        const appUsageChart = document.createElement('div');
        appUsageChart.className = 'space-y-2';
        
        // Add app usage data
        for (const app of appUsageHistory) {
            const appItem = document.createElement('div');
            appItem.className = 'flex items-center';
            
            // App name and color indicator
            const appInfo = document.createElement('div');
            appInfo.className = 'flex-1 flex items-center';
            
            const colorIndicator = document.createElement('div');
            colorIndicator.className = 'w-3 h-3 rounded-full mr-2';
            colorIndicator.style.backgroundColor = app.color;
            
            const appName = document.createElement('div');
            appName.className = 'text-sm text-gray-900 dark:text-gray-100';
            appName.textContent = app.name;
            
            const appCategory = document.createElement('div');
            appCategory.className = 'text-xs text-gray-500 dark:text-gray-400 ml-5';
            appCategory.textContent = app.category;
            
            appInfo.appendChild(colorIndicator);
            appInfo.appendChild(appName);
            
            // App duration bar
            const progressContainer = document.createElement('div');
            progressContainer.className = 'w-32 h-4 bg-gray-100 dark:bg-gray-700 rounded overflow-hidden';
            
            const progressBar = document.createElement('div');
            progressBar.className = 'h-full';
            progressBar.style.backgroundColor = app.color;
            progressBar.style.width = `${app.duration / 2}%`; // Scale for visual effect
            
            progressContainer.appendChild(progressBar);
            
            // App duration text
            const durationText = document.createElement('div');
            durationText.className = 'ml-2 text-xs text-gray-700 dark:text-gray-300 w-12 text-right';
            durationText.textContent = formatDuration(app.duration * 60); // Convert minutes to seconds
            
            // Add to item
            appItem.appendChild(appInfo);
            appItem.appendChild(progressContainer);
            appItem.appendChild(durationText);
            
            // Add to chart
            appUsageChart.appendChild(appItem);
        }
        
        appUsageContent.appendChild(appUsageChart);
        
        // Add tabs and content to container
        tabsContainer.appendChild(tabsHeader);
        tabsContainer.appendChild(appUsageContent);
        
        // Add tabs to recent tasks container
        recentTasksContainer.appendChild(tabsContainer);
        
        // Add tab switching functionality
        tabsHeader.addEventListener('click', function(e) {
            if (e.target.dataset.tab === 'app-usage') {
                // Show app usage, hide tasks
                tasksTab.classList.remove('text-blue-600', 'dark:text-blue-400', 'border-blue-600', 'dark:border-blue-400');
                tasksTab.classList.add('text-gray-500', 'dark:text-gray-400', 'border-transparent');
                
                appUsageTab.classList.remove('text-gray-500', 'dark:text-gray-400', 'border-transparent');
                appUsageTab.classList.add('text-blue-600', 'dark:text-blue-400', 'border-b-2', 'border-blue-600', 'dark:border-blue-400');
                
                document.querySelector('#solidJsCounter .space-y-2').style.display = 'none';
                appUsageContent.classList.remove('hidden');
            } else {
                // Show tasks, hide app usage
                appUsageTab.classList.remove('text-blue-600', 'dark:text-blue-400', 'border-blue-600', 'dark:border-blue-400');
                appUsageTab.classList.add('text-gray-500', 'dark:text-gray-400', 'border-transparent');
                
                tasksTab.classList.remove('text-gray-500', 'dark:text-gray-400', 'border-transparent');
                tasksTab.classList.add('text-blue-600', 'dark:text-blue-400', 'border-b-2', 'border-blue-600', 'dark:border-blue-400');
                
                document.querySelector('#solidJsCounter .space-y-2').style.display = 'block';
                appUsageContent.classList.add('hidden');
            }
        });
    }
    
    // Format timestamp to readable format
    function formatTimestamp(date) {
        const now = new Date();
        const diff = now - date;
        
        // Less than a minute
        if (diff < 60000) {
            return 'Just now';
        }
        
        // Less than an hour
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
        }
        
        // Today
        if (date.toDateString() === now.toDateString()) {
            return `Today at ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
        }
        
        // Yesterday
        const yesterday = new Date(now);
        yesterday.setDate(yesterday.getDate() - 1);
        if (date.toDateString() === yesterday.toDateString()) {
            return `Yesterday at ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
        }
        
        // Other dates
        return `${date.getDate()}/${date.getMonth() + 1}/${date.getFullYear()} at ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
    }
    
    // Show notification
    function showNotification(title, message) {
        // Create notification if it doesn't exist
        let notification = document.getElementById('pomodoro-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'pomodoro-notification';
            notification.className = 'fixed top-4 right-4 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-4 w-80 transform transition-transform duration-300 ease-in-out translate-x-full';
            document.body.appendChild(notification);
        }
        
        // Update content
        notification.innerHTML = `
            <div class="flex items-center justify-between">
                <div class="text-lg font-semibold text-gray-900 dark:text-white">${title}</div>
                <button id="pomodoro-notification-close" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="mt-2 text-gray-600 dark:text-gray-300">${message}</div>
            <div class="mt-4 flex justify-end">
                <button id="pomodoro-notification-action" class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    ${currentCycle === 'work' ? 'Start Break' : 'Start Work'}
                </button>
            </div>
        `;
        
        // Show notification
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Add event listeners
        document.getElementById('pomodoro-notification-close').addEventListener('click', () => {
            notification.classList.add('translate-x-full');
        });
        
        document.getElementById('pomodoro-notification-action').addEventListener('click', () => {
            notification.classList.add('translate-x-full');
            startTimer();
        });
        
        // Auto hide after 10 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
        }, 10000);
    }
    
    // Add Pomodoro mode toggle
    function addPomodoroToggle() {
        // Create toggle container
        const toggleContainer = document.createElement('div');
        toggleContainer.className = 'mt-4 flex items-center justify-center';
        
        // Create toggle label
        const toggleLabel = document.createElement('label');
        toggleLabel.className = 'flex items-center cursor-pointer';
        toggleLabel.innerHTML = `
            <div class="mr-3 text-sm font-medium text-gray-700 dark:text-gray-300">Pomodoro Mode</div>
            <div class="relative">
                <input id="pomodoro-toggle" type="checkbox" class="sr-only" />
                <div class="w-10 h-5 bg-gray-200 dark:bg-gray-700 rounded-full shadow-inner"></div>
                <div class="dot absolute w-5 h-5 bg-white rounded-full shadow -left-1 -top-0 transition"></div>
            </div>
        `;
        
        // Add styling for toggle
        const style = document.createElement('style');
        style.textContent = `
            #pomodoro-toggle:checked + .bg-gray-200 {
                background-color: #4f46e5;
            }
            #pomodoro-toggle:checked ~ .dot {
                transform: translateX(100%);
            }
        `;
        document.head.appendChild(style);
        
        // Add toggle to container
        toggleContainer.appendChild(toggleLabel);
        
        // Add toggle functionality
        toggleContainer.querySelector('#pomodoro-toggle').addEventListener('change', function(e) {
            pomodoroMode = e.target.checked;
            
            if (pomodoroMode) {
                // Reset timer for new Pomodoro cycle
                resetTimerValues();
                currentCycle = 'work';
                pomodoroConfig.currentPomodoros = 0;
                
                // Show initial instructions
                showNotification('Pomodoro Mode Activated', 'Work for 25 minutes, then take a short break. Every 4 pomodoros, take a longer break.');
            }
        });
        
        // Add container after timer
        const timerDisplay = document.querySelector('#solidJsCounter .text-6xl');
        timerDisplay.after(toggleContainer);
    }
    
    // Event listeners for timer controls
    startButton.addEventListener('click', function() {
        if (timerRunning) {
            pauseTimer();
        } else {
            startTimer();
        }
    });
    
    resetButton.addEventListener('click', resetTimer);
    
    // Add Pomodoro mode
    addPomodoroToggle();
    
    // Initialize task history display
    updateTaskHistory();
});