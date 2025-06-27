import { useEffect } from 'react';

interface RegisterRedirectProps {
    url: string;
}

export default function RegisterRedirect({ url }: RegisterRedirectProps) {
    useEffect(() => {
        // Use window.location.href for a full page redirect
        // This avoids CORS issues with cross-subdomain requests
        window.location.href = url;
    }, [url]);

    return (
        <div className="flex items-center justify-center min-h-screen">
            <div className="text-center">
                <h2 className="text-2xl font-semibold mb-2">Creating your workspace...</h2>
                <p className="text-muted-foreground">You will be redirected in a moment.</p>
            </div>
        </div>
    );
}