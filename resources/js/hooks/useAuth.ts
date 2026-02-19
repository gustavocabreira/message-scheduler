import { useQuery } from '@tanstack/react-query';
import api from '@/lib/api';
import { useAuthStore, type AuthUser } from '@/stores/auth.store';
import { useEffect } from 'react';

interface MeResponse {
    data: AuthUser;
}

export function useAuth() {
    const { user, isAuthenticated, setUser, logout } = useAuthStore();

    const { data, isLoading, error } = useQuery<MeResponse>({
        queryKey: ['auth', 'me'],
        queryFn: () => api.get<MeResponse>('/auth/me').then((r) => r.data),
        retry: false,
        staleTime: 5 * 60 * 1000,
    });

    useEffect(() => {
        if (data?.data) {
            setUser(data.data);
        } else if (error) {
            logout();
        }
    }, [data, error, setUser, logout]);

    return { user, isAuthenticated, isLoading };
}
