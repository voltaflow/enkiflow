import { useEffect, useRef, useCallback } from 'react';

interface BroadcastMessage {
    type: string;
    payload: any;
    timestamp: number;
    tabId: string;
}

interface UseBroadcastChannelOptions {
    channelName: string;
    onMessage?: (message: BroadcastMessage) => void;
    enabled?: boolean;
}

export function useBroadcastChannel({
    channelName,
    onMessage,
    enabled = true,
}: UseBroadcastChannelOptions) {
    const channelRef = useRef<BroadcastChannel | null>(null);
    const tabIdRef = useRef<string>(generateTabId());

    // Generate unique tab ID
    function generateTabId(): string {
        return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }

    // Send message to other tabs
    const sendMessage = useCallback((type: string, payload: any) => {
        if (!channelRef.current || !enabled) return;

        const message: BroadcastMessage = {
            type,
            payload,
            timestamp: Date.now(),
            tabId: tabIdRef.current,
        };

        try {
            channelRef.current.postMessage(message);
        } catch (error) {
            console.error('Error sending broadcast message:', error);
        }
    }, [enabled]);

    // Close channel
    const close = useCallback(() => {
        if (channelRef.current) {
            channelRef.current.close();
            channelRef.current = null;
        }
    }, []);

    useEffect(() => {
        if (!enabled || typeof BroadcastChannel === 'undefined') {
            return;
        }

        try {
            // Create or reuse channel
            channelRef.current = new BroadcastChannel(channelName);

            // Handle incoming messages
            channelRef.current.onmessage = (event: MessageEvent<BroadcastMessage>) => {
                const message = event.data;

                // Ignore messages from the same tab
                if (message.tabId === tabIdRef.current) {
                    return;
                }

                onMessage?.(message);
            };

            channelRef.current.onmessageerror = (event) => {
                console.error('BroadcastChannel message error:', event);
            };

            // Announce tab opened
            sendMessage('TAB_OPENED', { tabId: tabIdRef.current });

        } catch (error) {
            console.error('Error creating BroadcastChannel:', error);
        }

        // Cleanup
        return () => {
            // Announce tab closing
            sendMessage('TAB_CLOSING', { tabId: tabIdRef.current });
            close();
        };
    }, [channelName, enabled, onMessage, sendMessage, close]);

    return {
        sendMessage,
        close,
        tabId: tabIdRef.current,
        isSupported: typeof BroadcastChannel !== 'undefined',
    };
}

// Timer-specific broadcast channel hook
export function useTimerBroadcast(onTimerUpdate?: (data: any) => void) {
    const handleMessage = useCallback((message: BroadcastMessage) => {
        switch (message.type) {
            case 'TIMER_STARTED':
            case 'TIMER_STOPPED':
            case 'TIMER_PAUSED':
            case 'TIMER_RESUMED':
            case 'TIMER_UPDATED':
            case 'TIMER_SYNCED':
                onTimerUpdate?.(message.payload);
                break;
        }
    }, [onTimerUpdate]);

    const channel = useBroadcastChannel({
        channelName: 'enkiflow_timer_sync',
        onMessage: handleMessage,
    });

    return {
        ...channel,
        // Timer-specific methods
        broadcastTimerStart: (timerData: any) => 
            channel.sendMessage('TIMER_STARTED', timerData),
        broadcastTimerStop: (timerData: any) => 
            channel.sendMessage('TIMER_STOPPED', timerData),
        broadcastTimerPause: (timerData: any) => 
            channel.sendMessage('TIMER_PAUSED', timerData),
        broadcastTimerResume: (timerData: any) => 
            channel.sendMessage('TIMER_RESUMED', timerData),
        broadcastTimerUpdate: (timerData: any) => 
            channel.sendMessage('TIMER_UPDATED', timerData),
        broadcastTimerSync: (timerData: any) => 
            channel.sendMessage('TIMER_SYNCED', timerData),
    };
}