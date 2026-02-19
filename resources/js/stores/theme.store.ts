import { create } from 'zustand';
import { persist } from 'zustand/middleware';

type Theme = 'light' | 'dark' | 'system';

interface ThemeState {
    theme: Theme;
    setTheme: (theme: Theme) => void;
}

function applyTheme(theme: Theme): void {
    const root = document.documentElement;
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = theme === 'dark' || (theme === 'system' && prefersDark);

    root.classList.remove('light', 'dark');
    root.classList.add(isDark ? 'dark' : 'light');
}

export const useThemeStore = create<ThemeState>()(
    persist(
        (set) => ({
            theme: 'system',
            setTheme: (theme) => {
                applyTheme(theme);
                set({ theme });
            },
        }),
        { name: 'theme-storage' },
    ),
);

export { applyTheme };
