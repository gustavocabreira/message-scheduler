import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

interface AuthState {
    user: AuthUser | null;
    token: string | null;
    isAuthenticated: boolean;
    setUser: (user: AuthUser) => void;
    setToken: (token: string) => void;
    logout: () => void;
}

export const useAuthStore = create<AuthState>()(
    persist(
        (set) => ({
            user: null,
            token: null,
            isAuthenticated: false,
            setUser: (user) => set({ user }),
            setToken: (token) => set({ token, isAuthenticated: true }),
            logout: () => set({ user: null, token: null, isAuthenticated: false }),
        }),
        {
            name: 'auth-storage',
            partialize: (state) => ({
                user: state.user,
                token: state.token,
                isAuthenticated: state.isAuthenticated,
            }),
        },
    ),
);
