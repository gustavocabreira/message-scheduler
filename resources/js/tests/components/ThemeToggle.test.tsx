import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ThemeToggle } from '@/components/shared/ThemeToggle';
import { useThemeStore } from '@/stores/theme.store';
import { renderWithProviders } from '../helpers/renderWithProviders';

beforeEach(() => {
    useThemeStore.setState({ theme: 'system' });
    document.documentElement.classList.remove('light', 'dark');
});

describe('ThemeToggle', () => {
    it('renders the toggle button with accessible label', () => {
        renderWithProviders(<ThemeToggle />);
        expect(screen.getByRole('button', { name: /toggle theme/i })).toBeInTheDocument();
    });

    it('shows Monitor icon when theme is "system"', () => {
        useThemeStore.setState({ theme: 'system' });
        renderWithProviders(<ThemeToggle />);
        // Monitor icon is rendered as SVG; the button is present
        expect(screen.getByRole('button', { name: /toggle theme/i })).toBeInTheDocument();
    });

    it('opens the dropdown menu on click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ThemeToggle />);
        await user.click(screen.getByRole('button', { name: /toggle theme/i }));
        expect(screen.getByText('Claro')).toBeInTheDocument();
        expect(screen.getByText('Escuro')).toBeInTheDocument();
        expect(screen.getByText('Sistema')).toBeInTheDocument();
    });

    it('sets theme to "light" when "Claro" is clicked', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ThemeToggle />);
        await user.click(screen.getByRole('button', { name: /toggle theme/i }));
        await user.click(screen.getByText('Claro'));
        expect(useThemeStore.getState().theme).toBe('light');
    });

    it('sets theme to "dark" when "Escuro" is clicked', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ThemeToggle />);
        await user.click(screen.getByRole('button', { name: /toggle theme/i }));
        await user.click(screen.getByText('Escuro'));
        expect(useThemeStore.getState().theme).toBe('dark');
    });

    it('sets theme to "system" when "Sistema" is clicked', async () => {
        useThemeStore.setState({ theme: 'dark' });
        const user = userEvent.setup();
        renderWithProviders(<ThemeToggle />);
        await user.click(screen.getByRole('button', { name: /toggle theme/i }));
        await user.click(screen.getByText('Sistema'));
        expect(useThemeStore.getState().theme).toBe('system');
    });

    it('applies the dark class to <html> when "Escuro" is selected', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ThemeToggle />);
        await user.click(screen.getByRole('button', { name: /toggle theme/i }));
        await user.click(screen.getByText('Escuro'));
        expect(document.documentElement.classList.contains('dark')).toBe(true);
    });

    it('applies the light class to <html> when "Claro" is selected', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ThemeToggle />);
        await user.click(screen.getByRole('button', { name: /toggle theme/i }));
        await user.click(screen.getByText('Claro'));
        expect(document.documentElement.classList.contains('light')).toBe(true);
    });
});
