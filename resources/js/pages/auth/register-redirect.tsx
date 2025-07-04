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
        <div className="flex min-h-screen items-center justify-center">
            <div className="text-center">
                <h2 className="mb-2 text-2xl font-semibold">Creating your workspace...</h2>
                <p className="text-muted-foreground">You will be redirected in a moment.</p>
            </div>
        </div>
    );
}
