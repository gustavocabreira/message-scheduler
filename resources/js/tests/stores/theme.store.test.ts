import { useThemeStore, applyTheme } from '@/stores/theme.store';

function getStore() {
    return useThemeStore.getState();
}

beforeEach(() => {
    useThemeStore.setState({ theme: 'system' });
    document.documentElement.classList.remove('light', 'dark');
    // Reset matchMedia mock to return light preference by default
    (window.matchMedia as jest.Mock).mockImplementation((query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: jest.fn(),
        removeListener: jest.fn(),
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        dispatchEvent: jest.fn(),
    }));
});

describe('theme.store — initial state', () => {
    it('defaults to system theme', () => {
        expect(getStore().theme).toBe('system');
    });
});

describe('theme.store — setTheme()', () => {
    it('updates the theme to light', () => {
        getStore().setTheme('light');
        expect(getStore().theme).toBe('light');
    });

    it('updates the theme to dark', () => {
        getStore().setTheme('dark');
        expect(getStore().theme).toBe('dark');
    });

    it('updates the theme to system', () => {
        getStore().setTheme('light');
        getStore().setTheme('system');
        expect(getStore().theme).toBe('system');
    });
});

describe('applyTheme()', () => {
    it('adds "light" class to <html> for light theme', () => {
        applyTheme('light');
        expect(document.documentElement.classList.contains('light')).toBe(true);
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });

    it('adds "dark" class to <html> for dark theme', () => {
        applyTheme('dark');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
        expect(document.documentElement.classList.contains('light')).toBe(false);
    });

    it('adds "light" class for system theme when user prefers light', () => {
        (window.matchMedia as jest.Mock).mockReturnValue({ matches: false });
        applyTheme('system');
        expect(document.documentElement.classList.contains('light')).toBe(true);
    });

    it('adds "dark" class for system theme when user prefers dark', () => {
        (window.matchMedia as jest.Mock).mockReturnValue({ matches: true });
        applyTheme('system');
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('removes old theme class before adding new one', () => {
        applyTheme('dark');
        applyTheme('light');
        expect(document.documentElement.classList.contains('light')).toBe(true);
        expect(document.documentElement.classList.contains('dark')).toBe(false);
    });
});
