import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { useEffect, useState } from 'react';

interface Space {
    id: string;
    name: string;
    domains: { domain: string }[];
}

export function useSpaces() {
    const [spaces, setSpaces] = useState<Space[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const { auth } = usePage().props as any;

    useEffect(() => {
        // Solo hacer la petición si hay un usuario autenticado
        if (!auth?.user) {
            setLoading(false);
            return;
        }

        const fetchSpaces = async () => {
            try {
                setLoading(true);
                const response = await axios.get('/api/my-spaces');
                setSpaces(response.data);
                setError(null);
            } catch (err: any) {
                // Solo mostrar error si no es un 401 (no autenticado)
                if (err.response?.status !== 401) {
                    setError('Error al cargar los espacios');
                    console.error('Error fetching spaces:', err);
                }
            } finally {
                setLoading(false);
            }
        };

        fetchSpaces();
    }, [auth?.user]);

    return { spaces, loading, error };
}
