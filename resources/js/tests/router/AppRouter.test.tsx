import { screen } from '@testing-library/react';
import { render } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AppRouter } from '@/router';
import { useAuthStore } from '@/stores/auth.store';
import { useThemeStore } from '@/stores/theme.store';

jest.mock('@/lib/api', () => ({
    __esModule: true,
    default: {
        post: jest.fn(),
        get: jest.fn(),
        interceptors: {
            request: { use: jest.fn() },
            response: { use: jest.fn() },
        },
    },
}));

// Override BrowserRouter history to allow controlling initial URL
const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => {
    const actual = jest.requireActual<typeof import('react-router-dom')>('react-router-dom');
    return {
        ...actual,
        useNavigate: () => mockNavigate,
        BrowserRouter: ({ children }: { children: React.ReactNode }) => {
            const { MemoryRouter } = actual;
            return <MemoryRouter initialEntries={[currentPath]}>{children}</MemoryRouter>;
        },
    };
});

let currentPath = '/login';

function renderRouter(path = '/login') {
    currentPath = path;
    const queryClient = new QueryClient({
        defaultOptions: { queries: { retry: false } },
    });
    return render(
        <QueryClientProvider client={queryClient}>
            <AppRouter />
        </QueryClientProvider>,
    );
}

const mockUser = { id: 1, name: 'Ada Lovelace', email: 'ada@example.com' };

beforeEach(() => {
    useAuthStore.setState({ user: null, token: null, isAuthenticated: false });
    useThemeStore.setState({ theme: 'system' });
    mockNavigate.mockClear();
});

describe('AppRouter — unauthenticated user', () => {
    it('renders the Login page at /login', () => {
        renderRouter('/login');
        expect(screen.getByRole('heading', { name: /entrar/i })).toBeInTheDocument();
    });

    it('renders the Register page at /register', () => {
        renderRouter('/register');
        expect(screen.getByRole('heading', { name: /criar conta/i })).toBeInTheDocument();
    });

    it('redirects from /dashboard to /login when unauthenticated', () => {
        renderRouter('/dashboard');
        // PrivateRoute redirects to /login, which renders the Login page
        expect(screen.getByRole('heading', { name: /entrar/i })).toBeInTheDocument();
    });

    it('redirects from / to /login when unauthenticated', () => {
        renderRouter('/');
        // / → /dashboard → /login (unauthenticated)
        expect(screen.getByRole('heading', { name: /entrar/i })).toBeInTheDocument();
    });
});

describe('AppRouter — authenticated user', () => {
    beforeEach(() => {
        useAuthStore.setState({ user: mockUser, token: 'test-token', isAuthenticated: true });
    });

    it('renders the Dashboard page at /dashboard', () => {
        renderRouter('/dashboard');
        expect(screen.getByText(/olá, ada/i)).toBeInTheDocument();
    });

    it('redirects from /login to /dashboard when authenticated', () => {
        renderRouter('/login');
        // PublicRoute redirects to /dashboard
        expect(screen.getByText(/olá, ada/i)).toBeInTheDocument();
    });

    it('redirects from /register to /dashboard when authenticated', () => {
        renderRouter('/register');
        expect(screen.getByText(/olá, ada/i)).toBeInTheDocument();
    });

    it('renders sidebar navigation links in the AppLayout', () => {
        renderRouter('/dashboard');
        expect(screen.getByRole('link', { name: /dashboard/i })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /providers/i })).toBeInTheDocument();
        expect(screen.getByRole('link', { name: /agendamentos/i })).toBeInTheDocument();
    });
});
