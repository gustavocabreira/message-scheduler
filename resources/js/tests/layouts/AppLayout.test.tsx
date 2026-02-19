import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { render } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AppLayout } from '@/layouts/AppLayout';
import { useAuthStore } from '@/stores/auth.store';
import { useThemeStore } from '@/stores/theme.store';

jest.mock('@/lib/api', () => ({
    __esModule: true,
    default: { post: jest.fn(), get: jest.fn() },
    initCsrf: jest.fn().mockResolvedValue(undefined),
}));

const mockUser = { id: 1, name: 'Ada Lovelace', email: 'ada@example.com' };

function renderAppLayout(initialEntry = '/dashboard') {
    const queryClient = new QueryClient({
        defaultOptions: { queries: { retry: false } },
    });

    return render(
        <QueryClientProvider client={queryClient}>
            <MemoryRouter initialEntries={[initialEntry]}>
                <Routes>
                    <Route element={<AppLayout />}>
                        <Route path="/dashboard" element={<p>Dashboard content</p>} />
                        <Route path="/providers" element={<p>Providers content</p>} />
                        <Route path="/scheduled-messages" element={<p>Scheduled content</p>} />
                    </Route>
                </Routes>
            </MemoryRouter>
        </QueryClientProvider>,
    );
}

beforeEach(() => {
    useAuthStore.setState({ user: mockUser, isAuthenticated: true });
    useThemeStore.setState({ theme: 'system' });
    document.documentElement.classList.remove('light', 'dark');
});

describe('AppLayout — sidebar navigation', () => {
    it('renders the app brand name in the sidebar', () => {
        renderAppLayout();
        const brandInstances = screen.getAllByText('Message Scheduler');
        expect(brandInstances.length).toBeGreaterThanOrEqual(1);
    });

    it('renders all sidebar navigation links', () => {
        renderAppLayout();
        expect(screen.getByRole('link', { name: /dashboard/i })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /providers/i })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /agendamentos/i })).toBeInTheDocument();
    });

    it('renders the outlet content', () => {
        renderAppLayout('/dashboard');
        expect(screen.getByText('Dashboard content')).toBeInTheDocument();
    });

    it('renders different content at /providers', () => {
        renderAppLayout('/providers');
        expect(screen.getByText('Providers content')).toBeInTheDocument();
    });
});

describe('AppLayout — header', () => {
    it('renders the theme toggle button', () => {
        renderAppLayout();
        expect(screen.getByRole('button', { name: /toggle theme/i })).toBeInTheDocument();
    });

    it('renders the user avatar button', () => {
        renderAppLayout();
        // UserMenu renders a button with the user's initials
        const initialsButton = screen.getByRole('button', { name: /AL/i });
        expect(initialsButton).toBeInTheDocument();
    });

    it('shows user name and email in the dropdown', async () => {
        const user = userEvent.setup();
        renderAppLayout();
        await user.click(screen.getByRole('button', { name: /AL/i }));
        expect(screen.getByText('Ada Lovelace')).toBeInTheDocument();
        expect(screen.getByText('ada@example.com')).toBeInTheDocument();
    });

    it('renders the logout menu item', async () => {
        const user = userEvent.setup();
        renderAppLayout();
        await user.click(screen.getByRole('button', { name: /AL/i }));
        expect(screen.getByRole('menuitem', { name: /sair/i })).toBeInTheDocument();
    });
});

describe('AppLayout — active nav link', () => {
    it('marks the Dashboard link as active on /dashboard', () => {
        renderAppLayout('/dashboard');
        const dashboardLink = screen.getByRole('link', { name: /dashboard/i });
        expect(dashboardLink.className).toContain('bg-primary');
    });

    it('marks the Providers link as active on /providers', () => {
        renderAppLayout('/providers');
        const providersLink = screen.getByRole('link', { name: /providers/i });
        expect(providersLink.className).toContain('bg-primary');
    });
});
