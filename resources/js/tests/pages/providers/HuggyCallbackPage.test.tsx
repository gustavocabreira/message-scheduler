import { screen, waitFor } from '@testing-library/react';
import { HuggyCallbackPage } from '@/pages/providers/HuggyCallbackPage';
import { renderWithProviders } from '../../helpers/renderWithProviders';

jest.mock('@/lib/api', () => ({
    __esModule: true,
    default: { get: jest.fn(), post: jest.fn(), delete: jest.fn() },
}));

import api from '@/lib/api';
const mockedApi = api as jest.Mocked<typeof api>;

const mockNavigate = jest.fn();

jest.mock('react-router-dom', () => {
    const actual = jest.requireActual<typeof import('react-router-dom')>('react-router-dom');
    return {
        ...actual,
        useNavigate: () => mockNavigate,
    };
});

beforeEach(() => {
    jest.clearAllMocks();
    mockNavigate.mockClear();
});

describe('HuggyCallbackPage — missing code', () => {
    it('shows error state when no code query param is present', () => {
        renderWithProviders(<HuggyCallbackPage />, {
            routerProps: { initialEntries: ['/providers/huggy/callback'] },
        });

        expect(screen.getByText(/código de autorização ausente/i)).toBeInTheDocument();
    });

    it('renders a link back to providers when code is missing', () => {
        renderWithProviders(<HuggyCallbackPage />, {
            routerProps: { initialEntries: ['/providers/huggy/callback'] },
        });

        expect(screen.getByRole('link', { name: /voltar para providers/i })).toBeInTheDocument();
    });

    it('does not call the callback API when code is missing', () => {
        renderWithProviders(<HuggyCallbackPage />, {
            routerProps: { initialEntries: ['/providers/huggy/callback'] },
        });
        expect(mockedApi.get).not.toHaveBeenCalled();
    });
});

describe('HuggyCallbackPage — with code (loading)', () => {
    it('shows loading state while processing the code', () => {
        let resolve: (v: unknown) => void;
        mockedApi.get.mockReturnValueOnce(new Promise((res) => { resolve = res; }) as never);

        renderWithProviders(<HuggyCallbackPage />, {
            routerProps: { initialEntries: ['/providers/huggy/callback?code=auth_code_123'] },
        });

        expect(screen.getByText(/conectando com a huggy/i)).toBeInTheDocument();
        resolve!({ data: { message: 'ok' } });
    });

    it('calls GET /auth/huggy/callback with the code param', async () => {
        mockedApi.get.mockResolvedValueOnce({
            data: { message: 'Huggy account connected successfully.' },
        });

        renderWithProviders(<HuggyCallbackPage />, {
            routerProps: { initialEntries: ['/providers/huggy/callback?code=auth_code_123'] },
        });

        await waitFor(() => {
            expect(mockedApi.get).toHaveBeenCalledWith('/auth/huggy/callback', {
                params: { code: 'auth_code_123' },
            });
        });
    });
});

describe('HuggyCallbackPage — successful callback', () => {
    it('navigates to /providers on success', async () => {
        mockedApi.get.mockResolvedValueOnce({
            data: { message: 'Huggy account connected successfully.' },
        });

        renderWithProviders(<HuggyCallbackPage />, {
            routerProps: { initialEntries: ['/providers/huggy/callback?code=auth_code_123'] },
        });

        await waitFor(() => {
            expect(mockNavigate).toHaveBeenCalledWith('/providers', { replace: true });
        });
    });

    it('does not call the API more than once (StrictMode protection)', async () => {
        mockedApi.get.mockResolvedValue({
            data: { message: 'Huggy account connected successfully.' },
        });

        renderWithProviders(<HuggyCallbackPage />, {
            routerProps: { initialEntries: ['/providers/huggy/callback?code=auth_code_123'] },
        });

        await waitFor(() => {
            expect(mockedApi.get).toHaveBeenCalledTimes(1);
        });
    });
});

describe('HuggyCallbackPage — failed callback', () => {
    it('shows error state when the API call fails', async () => {
        mockedApi.get.mockRejectedValueOnce({
            response: { data: { message: 'Token exchange failed.' } },
        });

        renderWithProviders(<HuggyCallbackPage />, {
            routerProps: { initialEntries: ['/providers/huggy/callback?code=bad_code'] },
        });

        await waitFor(() => {
            expect(screen.getByText(/falha ao conectar com a huggy/i)).toBeInTheDocument();
        });
    });

    it('shows the API error message in the error state', async () => {
        mockedApi.get.mockRejectedValueOnce({
            response: { data: { message: 'Token exchange failed.' } },
        });

        renderWithProviders(<HuggyCallbackPage />, {
            routerProps: { initialEntries: ['/providers/huggy/callback?code=bad_code'] },
        });

        await waitFor(() => {
            expect(screen.getByText(/token exchange failed/i)).toBeInTheDocument();
        });
    });

    it('renders a link back to providers in the error state', async () => {
        mockedApi.get.mockRejectedValueOnce({
            response: { data: { message: 'Token exchange failed.' } },
        });

        renderWithProviders(<HuggyCallbackPage />, {
            routerProps: { initialEntries: ['/providers/huggy/callback?code=bad_code'] },
        });

        await waitFor(() => {
            expect(screen.getByRole('link', { name: /voltar para providers/i })).toBeInTheDocument();
        });
    });

    it('does not navigate on failure', async () => {
        mockedApi.get.mockRejectedValueOnce({
            response: { data: { message: 'Error.' } },
        });

        renderWithProviders(<HuggyCallbackPage />, {
            routerProps: { initialEntries: ['/providers/huggy/callback?code=bad_code'] },
        });

        await waitFor(() => {
            expect(screen.getByText(/falha ao conectar/i)).toBeInTheDocument();
        });

        expect(mockNavigate).not.toHaveBeenCalled();
    });
});
