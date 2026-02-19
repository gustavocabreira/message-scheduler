import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { RegisterPage } from '@/pages/auth/Register';
import { useAuthStore } from '@/stores/auth.store';
import { renderWithProviders } from '../../helpers/renderWithProviders';

jest.mock('@/lib/api', () => ({
    __esModule: true,
    default: {
        post: jest.fn(),
        get: jest.fn(),
    },
    initCsrf: jest.fn().mockResolvedValue(undefined),
}));

import api, { initCsrf } from '@/lib/api';

const mockedApi = api as jest.Mocked<typeof api>;
const mockedInitCsrf = initCsrf as jest.Mock;

const mockUser = { id: 2, name: 'Grace Hopper', email: 'grace@example.com' };

async function fillForm(
    user: ReturnType<typeof userEvent.setup>,
    overrides: Partial<{
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
    }> = {},
) {
    const values = {
        name: 'Grace Hopper',
        email: 'grace@example.com',
        password: 'password123',
        password_confirmation: 'password123',
        ...overrides,
    };
    await user.type(screen.getByLabelText(/^nome$/i), values.name);
    await user.type(screen.getByLabelText(/^e-mail$/i), values.email);
    await user.type(screen.getByLabelText(/^senha$/i), values.password);
    await user.type(screen.getByLabelText(/^confirmar senha$/i), values.password_confirmation);
}

beforeEach(() => {
    jest.clearAllMocks();
    useAuthStore.setState({ user: null, isAuthenticated: false });
});

describe('RegisterPage — rendering', () => {
    it('renders the heading', () => {
        renderWithProviders(<RegisterPage />);
        expect(screen.getByRole('heading', { name: /criar conta/i })).toBeInTheDocument();
    });

    it('renders all four form fields', () => {
        renderWithProviders(<RegisterPage />);
        expect(screen.getByLabelText(/^nome$/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/^e-mail$/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/^senha$/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/^confirmar senha$/i)).toBeInTheDocument();
    });

    it('renders the submit button', () => {
        renderWithProviders(<RegisterPage />);
        expect(screen.getByRole('button', { name: /criar conta/i })).toBeInTheDocument();
    });

    it('renders the link to login page', () => {
        renderWithProviders(<RegisterPage />);
        expect(screen.getByRole('link', { name: /faça login/i })).toBeInTheDocument();
    });
});

describe('RegisterPage — form validation', () => {
    it('shows error when name is too short', async () => {
        const user = userEvent.setup();
        renderWithProviders(<RegisterPage />);
        await user.type(screen.getByLabelText(/^nome$/i), 'A');
        await user.click(screen.getByRole('button', { name: /criar conta/i }));
        await waitFor(() => {
            expect(screen.getByText(/pelo menos 2 caracteres/i)).toBeInTheDocument();
        });
    });

    it('shows error for invalid email', async () => {
        const user = userEvent.setup();
        renderWithProviders(<RegisterPage />);
        await user.type(screen.getByLabelText(/^nome$/i), 'Grace Hopper');
        await user.type(screen.getByLabelText(/^e-mail$/i), 'not-an-email');
        await user.click(screen.getByRole('button', { name: /criar conta/i }));
        await waitFor(() => {
            expect(screen.getByText(/informe um e-mail válido/i)).toBeInTheDocument();
        });
    });

    it('shows error when password is too short', async () => {
        const user = userEvent.setup();
        renderWithProviders(<RegisterPage />);
        await fillForm(user, { password: 'short', password_confirmation: 'short' });
        await user.click(screen.getByRole('button', { name: /criar conta/i }));
        await waitFor(() => {
            expect(screen.getByText(/pelo menos 8 caracteres/i)).toBeInTheDocument();
        });
    });

    it('shows error when passwords do not match', async () => {
        const user = userEvent.setup();
        renderWithProviders(<RegisterPage />);
        await fillForm(user, { password: 'password123', password_confirmation: 'different456' });
        await user.click(screen.getByRole('button', { name: /criar conta/i }));
        await waitFor(() => {
            expect(screen.getByText(/as senhas não coincidem/i)).toBeInTheDocument();
        });
    });
});

describe('RegisterPage — successful registration', () => {
    it('calls initCsrf and POST /auth/register with the correct payload', async () => {
        mockedApi.post.mockResolvedValueOnce({ data: { data: mockUser } });
        const user = userEvent.setup();
        renderWithProviders(<RegisterPage />);
        await fillForm(user);
        await user.click(screen.getByRole('button', { name: /criar conta/i }));

        await waitFor(() => {
            expect(mockedInitCsrf).toHaveBeenCalledTimes(1);
            expect(mockedApi.post).toHaveBeenCalledWith('/auth/register', {
                name: 'Grace Hopper',
                email: 'grace@example.com',
                password: 'password123',
                password_confirmation: 'password123',
            });
        });
    });

    it('stores the user in the auth store after successful registration', async () => {
        mockedApi.post.mockResolvedValueOnce({ data: { data: mockUser } });
        const user = userEvent.setup();
        renderWithProviders(<RegisterPage />);
        await fillForm(user);
        await user.click(screen.getByRole('button', { name: /criar conta/i }));

        await waitFor(() => {
            expect(useAuthStore.getState().user).toEqual(mockUser);
            expect(useAuthStore.getState().isAuthenticated).toBe(true);
        });
    });
});

describe('RegisterPage — failed registration', () => {
    it('maps per-field API validation errors to the correct fields', async () => {
        mockedApi.post.mockRejectedValueOnce({
            response: {
                data: {
                    errors: {
                        email: ['O e-mail já está em uso.'],
                    },
                },
            },
        });
        const user = userEvent.setup();
        renderWithProviders(<RegisterPage />);
        await fillForm(user);
        await user.click(screen.getByRole('button', { name: /criar conta/i }));

        await waitFor(() => {
            expect(screen.getByText(/o e-mail já está em uso/i)).toBeInTheDocument();
        });
    });

    it('shows generic error message when API returns only a message', async () => {
        mockedApi.post.mockRejectedValueOnce({
            response: { data: { message: 'Erro interno do servidor.' } },
        });
        const user = userEvent.setup();
        renderWithProviders(<RegisterPage />);
        await fillForm(user);
        await user.click(screen.getByRole('button', { name: /criar conta/i }));

        await waitFor(() => {
            expect(screen.getByText(/erro interno do servidor/i)).toBeInTheDocument();
        });
    });

    it('does not update the auth store on failed registration', async () => {
        mockedApi.post.mockRejectedValueOnce({
            response: { data: { message: 'Unprocessable Entity' } },
        });
        const user = userEvent.setup();
        renderWithProviders(<RegisterPage />);
        await fillForm(user);
        await user.click(screen.getByRole('button', { name: /criar conta/i }));

        await waitFor(() => {
            expect(useAuthStore.getState().isAuthenticated).toBe(false);
        });
    });
});
