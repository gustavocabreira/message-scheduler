import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { LoginPage } from '@/pages/auth/Login';
import { useAuthStore } from '@/stores/auth.store';
import { renderWithProviders } from '../../helpers/renderWithProviders';

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

import api from '@/lib/api';

const mockedApi = api as jest.Mocked<typeof api>;

const mockToken = 'test-sanctum-token-abc123';

beforeEach(() => {
    jest.clearAllMocks();
    useAuthStore.setState({ user: null, token: null, isAuthenticated: false });
});

describe('LoginPage — rendering', () => {
    it('renders the page heading', () => {
        renderWithProviders(<LoginPage />);
        expect(screen.getByRole('heading', { name: /entrar/i })).toBeInTheDocument();
    });

    it('renders email and password fields', () => {
        renderWithProviders(<LoginPage />);
        expect(screen.getByLabelText(/e-mail/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/senha/i)).toBeInTheDocument();
    });

    it('renders the submit button', () => {
        renderWithProviders(<LoginPage />);
        expect(screen.getByRole('button', { name: /entrar/i })).toBeInTheDocument();
    });

    it('renders the link to register page', () => {
        renderWithProviders(<LoginPage />);
        expect(screen.getByRole('link', { name: /registre-se/i })).toBeInTheDocument();
    });

    it('renders disabled OAuth buttons', () => {
        renderWithProviders(<LoginPage />);
        expect(screen.getByRole('button', { name: /google/i })).toBeDisabled();
        expect(screen.getByRole('button', { name: /github/i })).toBeDisabled();
    });
});

describe('LoginPage — form validation', () => {
    it('shows email validation error when email is empty', async () => {
        const user = userEvent.setup();
        renderWithProviders(<LoginPage />);
        await user.click(screen.getByRole('button', { name: /^entrar$/i }));
        await waitFor(() => {
            expect(screen.getByText(/informe um e-mail válido/i)).toBeInTheDocument();
        });
    });

    it('shows email validation error for invalid email format', async () => {
        const user = userEvent.setup();
        renderWithProviders(<LoginPage />);
        await user.type(screen.getByLabelText(/e-mail/i), 'not-an-email');
        await user.click(screen.getByRole('button', { name: /^entrar$/i }));
        await waitFor(() => {
            expect(screen.getByText(/informe um e-mail válido/i)).toBeInTheDocument();
        });
    });

    it('shows password validation error when password is empty', async () => {
        const user = userEvent.setup();
        renderWithProviders(<LoginPage />);
        await user.type(screen.getByLabelText(/e-mail/i), 'user@example.com');
        await user.click(screen.getByRole('button', { name: /^entrar$/i }));
        await waitFor(() => {
            expect(screen.getByText(/informe sua senha/i)).toBeInTheDocument();
        });
    });
});

describe('LoginPage — successful login', () => {
    it('calls POST /auth/login with correct payload', async () => {
        mockedApi.post.mockResolvedValueOnce({ data: { message: 'Login successful.', token: mockToken } });
        const user = userEvent.setup();
        renderWithProviders(<LoginPage />);

        await user.type(screen.getByLabelText(/e-mail/i), 'ada@example.com');
        await user.type(screen.getByLabelText(/senha/i), 'secret123');
        await user.click(screen.getByRole('button', { name: /^entrar$/i }));

        await waitFor(() => {
            expect(mockedApi.post).toHaveBeenCalledWith('/auth/login', {
                email: 'ada@example.com',
                password: 'secret123',
            });
        });
    });

    it('stores the token in the auth store and marks as authenticated after successful login', async () => {
        mockedApi.post.mockResolvedValueOnce({ data: { message: 'Login successful.', token: mockToken } });
        const user = userEvent.setup();
        renderWithProviders(<LoginPage />);

        await user.type(screen.getByLabelText(/e-mail/i), 'ada@example.com');
        await user.type(screen.getByLabelText(/senha/i), 'secret123');
        await user.click(screen.getByRole('button', { name: /^entrar$/i }));

        await waitFor(() => {
            expect(useAuthStore.getState().token).toBe(mockToken);
            expect(useAuthStore.getState().isAuthenticated).toBe(true);
        });
    });
});

describe('LoginPage — failed login', () => {
    it('shows API error message on 401 response', async () => {
        mockedApi.post.mockRejectedValueOnce({
            response: { data: { message: 'Credenciais inválidas.' } },
        });
        const user = userEvent.setup();
        renderWithProviders(<LoginPage />);

        await user.type(screen.getByLabelText(/e-mail/i), 'ada@example.com');
        await user.type(screen.getByLabelText(/senha/i), 'wrongpass');
        await user.click(screen.getByRole('button', { name: /^entrar$/i }));

        await waitFor(() => {
            expect(screen.getByText(/credenciais inválidas/i)).toBeInTheDocument();
        });
    });

    it('shows fallback error message when API response has no message', async () => {
        mockedApi.post.mockRejectedValueOnce({ response: {} });
        const user = userEvent.setup();
        renderWithProviders(<LoginPage />);

        await user.type(screen.getByLabelText(/e-mail/i), 'ada@example.com');
        await user.type(screen.getByLabelText(/senha/i), 'wrongpass');
        await user.click(screen.getByRole('button', { name: /^entrar$/i }));

        await waitFor(() => {
            expect(screen.getByText(/credenciais inválidas/i)).toBeInTheDocument();
        });
    });

    it('does not update the auth store on failed login', async () => {
        mockedApi.post.mockRejectedValueOnce({
            response: { data: { message: 'Unauthorized' } },
        });
        const user = userEvent.setup();
        renderWithProviders(<LoginPage />);

        await user.type(screen.getByLabelText(/e-mail/i), 'ada@example.com');
        await user.type(screen.getByLabelText(/senha/i), 'wrongpass');
        await user.click(screen.getByRole('button', { name: /^entrar$/i }));

        await waitFor(() => {
            expect(useAuthStore.getState().token).toBeNull();
            expect(useAuthStore.getState().isAuthenticated).toBe(false);
        });
    });
});
