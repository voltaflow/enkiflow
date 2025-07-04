/**
 * Time formatting utilities
 */

/**
 * Format seconds to MM:SS format
 * @param seconds - Duration in seconds
 * @returns Formatted string in MM:SS format
 */
export function formatDurationMMSS(seconds: number): string {
    if (!seconds || seconds === 0) return '00:00';

    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;

    return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

/**
 * Format seconds to HH:MM format for longer durations
 * @param seconds - Duration in seconds
 * @returns Formatted string in HH:MM format
 */
export function formatDurationHHMM(seconds: number): string {
    if (!seconds || seconds === 0) return '00:00';

    // Handle negative durations (shouldn't happen but protects against backend issues)
    const absSeconds = Math.abs(seconds);
    const isNegative = seconds < 0;

    const hours = Math.floor(absSeconds / 3600);
    const minutes = Math.floor((absSeconds % 3600) / 60);

    const formatted = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;

    // Show negative sign if duration is negative (for debugging)
    return isNegative ? `-${formatted}` : formatted;
}

/**
 * Format seconds to HH:MM:SS format
 * @param seconds - Duration in seconds
 * @returns Formatted string in HH:MM:SS format
 */
export function formatDurationHHMMSS(seconds: number): string {
    if (!seconds || seconds === 0) return '00:00:00';

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
}

/**
 * Format seconds to decimal hours (e.g., 1.5h)
 * @param seconds - Duration in seconds
 * @returns Formatted string with decimal hours
 */
export function formatSecondsToHours(seconds: number): string {
    if (!seconds || seconds === 0) return '0.0';

    const hours = seconds / 3600;
    return hours.toFixed(1);
}

/**
 * Format hours to display format
 * @param hours - Duration in hours
 * @returns Formatted string (e.g., "1.5" or "-" for zero)
 */
export function formatHours(hours: number): string {
    if (hours === 0) return '-';
    return hours.toFixed(1);
}

/**
 * Parse MM:SS format to seconds
 * @param duration - Duration string in MM:SS format
 * @returns Duration in seconds
 */
export function parseDurationMMSS(duration: string): number {
    const parts = duration.split(':');
    if (parts.length !== 2) return 0;

    const minutes = parseInt(parts[0], 10) || 0;
    const seconds = parseInt(parts[1], 10) || 0;

    return minutes * 60 + seconds;
}

/**
 * Parse HH:MM format to seconds
 * @param duration - Duration string in HH:MM format
 * @returns Duration in seconds
 */
export function parseDurationHHMM(duration: string): number {
    const parts = duration.split(':');
    if (parts.length !== 2) return 0;

    const hours = parseInt(parts[0], 10) || 0;
    const minutes = parseInt(parts[1], 10) || 0;

    return hours * 3600 + minutes * 60;
}

/**
 * Parse HH:MM:SS format to seconds
 * @param duration - Duration string in HH:MM:SS format
 * @returns Duration in seconds
 */
export function parseDurationHHMMSS(duration: string): number {
    const parts = duration.split(':');
    if (parts.length !== 3) return 0;

    const hours = parseInt(parts[0], 10) || 0;
    const minutes = parseInt(parts[1], 10) || 0;
    const seconds = parseInt(parts[2], 10) || 0;

    return hours * 3600 + minutes * 60 + seconds;
}
