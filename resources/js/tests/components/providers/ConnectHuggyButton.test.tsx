import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ConnectHuggyButton } from '@/components/providers/ConnectHuggyButton';
import { renderWithProviders } from '../../helpers/renderWithProviders';

jest.mock('@/lib/api', () => ({
    __esModule: true,
    default: { get: jest.fn(), post: jest.fn(), delete: jest.fn() },
}));

jest.mock('@/lib/navigate', () => ({
    navigateTo: jest.fn(),
}));

jest.mock('sonner', () => ({
    toast: { success: jest.fn(), warning: jest.fn(), error: jest.fn() },
}));

import api from '@/lib/api';
import { toast } from 'sonner';
import { navigateTo } from '@/lib/navigate';
const mockedApi = api as jest.Mocked<typeof api>;
const mockedToast = toast as jest.Mocked<typeof toast>;
const mockedNavigateTo = navigateTo as jest.Mock;

beforeEach(() => {
    jest.clearAllMocks();
});

describe('ConnectHuggyButton', () => {
    it('renders the button with correct label', () => {
        renderWithProviders(<ConnectHuggyButton />);
        expect(screen.getByRole('button', { name: /conectar huggy/i })).toBeInTheDocument();
    });

    it('is not disabled initially', () => {
        renderWithProviders(<ConnectHuggyButton />);
        expect(screen.getByRole('button', { name: /conectar huggy/i })).not.toBeDisabled();
    });

    it('calls GET /auth/huggy/redirect on click', async () => {
        const authUrl = 'https://huggy.io/oauth/authorize?client_id=test';
        mockedApi.get.mockResolvedValueOnce({ data: { authorization_url: authUrl } });

        const user = userEvent.setup();
        renderWithProviders(<ConnectHuggyButton />);

        await user.click(screen.getByRole('button', { name: /conectar huggy/i }));

        await waitFor(() => {
            expect(mockedApi.get).toHaveBeenCalledWith('/auth/huggy/redirect');
        });
    });

    it('calls navigateTo with authorization_url on success', async () => {
        const authUrl = 'https://huggy.io/oauth/authorize?client_id=test';
        mockedApi.get.mockResolvedValueOnce({ data: { authorization_url: authUrl } });

        const user = userEvent.setup();
        renderWithProviders(<ConnectHuggyButton />);

        await user.click(screen.getByRole('button', { name: /conectar huggy/i }));

        await waitFor(() => {
            expect(mockedNavigateTo).toHaveBeenCalledWith(authUrl);
        });
    });

    it('disables the button while the request is pending', async () => {
        let resolve: (v: unknown) => void;
        const pending = new Promise((res) => { resolve = res; });
        mockedApi.get.mockReturnValueOnce(pending as never);

        const user = userEvent.setup();
        renderWithProviders(<ConnectHuggyButton />);
        await user.click(screen.getByRole('button', { name: /conectar huggy/i }));

        expect(screen.getByRole('button', { name: /conectar huggy/i })).toBeDisabled();

        resolve!({ data: { authorization_url: '' } });
    });

    it('shows an error toast when the API call fails', async () => {
        mockedApi.get.mockRejectedValueOnce(new Error('Network error'));

        const user = userEvent.setup();
        renderWithProviders(<ConnectHuggyButton />);
        await user.click(screen.getByRole('button', { name: /conectar huggy/i }));

        await waitFor(() => {
            expect(mockedToast.error).toHaveBeenCalled();
        });
    });

    it('renders with outline variant when specified', () => {
        renderWithProviders(<ConnectHuggyButton variant="outline" />);
        expect(screen.getByRole('button', { name: /conectar huggy/i })).toBeInTheDocument();
    });
});
