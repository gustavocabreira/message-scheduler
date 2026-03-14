import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { CreateScheduledMessageDialog } from '@/components/scheduled-messages/CreateScheduledMessageDialog';
import { renderWithProviders } from '../../helpers/renderWithProviders';
import type { ProviderConnection } from '@/types/provider';
import type { ScheduledMessage } from '@/types/scheduled-message';

jest.mock('@/lib/api', () => ({
    __esModule: true,
    default: { get: jest.fn(), post: jest.fn(), delete: jest.fn() },
}));

jest.mock('sonner', () => ({
    toast: { success: jest.fn(), error: jest.fn() },
}));

import api from '@/lib/api';
import { toast } from 'sonner';
const mockedApi = api as jest.Mocked<typeof api>;
const mockedToast = toast as jest.Mocked<typeof toast>;

const mockProvider: ProviderConnection = {
    id: 1,
    provider_type: 'huggy',
    provider_label: 'Huggy',
    status: 'active',
    settings: null,
    connected_at: '2026-02-18T21:00:00Z',
    last_synced_at: null,
    created_at: '2026-02-18T21:00:00Z',
};

const mockMessage: ScheduledMessage = {
    id: 1,
    contact_id: 'c-1',
    contact_name: 'Alice Smith',
    message: 'Hello!',
    scheduled_at: '2026-03-01T10:00:00Z',
    status: 'pending',
    attempts: 0,
    provider_connection_id: 1,
    created_at: '2026-02-18T21:00:00Z',
    updated_at: '2026-02-18T21:00:00Z',
    provider_connection: { id: 1, provider_type: 'huggy', provider_label: 'Huggy' },
};

function renderDialog(open = true) {
    const onOpenChange = jest.fn();
    mockedApi.get.mockResolvedValueOnce({ data: { data: [mockProvider] } });
    const result = renderWithProviders(
        <CreateScheduledMessageDialog open={open} onOpenChange={onOpenChange} />,
    );
    return { ...result, onOpenChange };
}

beforeEach(() => {
    jest.clearAllMocks();
});

describe('CreateScheduledMessageDialog — rendering', () => {
    it('does not render when open is false', () => {
        mockedApi.get.mockResolvedValueOnce({ data: { data: [] } });
        renderWithProviders(
            <CreateScheduledMessageDialog open={false} onOpenChange={jest.fn()} />,
        );
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });

    it('renders the dialog when open is true', () => {
        renderDialog();
        expect(screen.getByRole('dialog')).toBeInTheDocument();
    });

    it('renders all form fields', () => {
        renderDialog();
        expect(screen.getByLabelText(/provider/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/id do contato/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/nome do contato/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/mensagem/i)).toBeInTheDocument();
        expect(screen.getByLabelText(/data e hora/i)).toBeInTheDocument();
    });

    it('renders submit and cancel buttons', () => {
        renderDialog();
        expect(screen.getByRole('button', { name: /^agendar$/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /^cancelar$/i })).toBeInTheDocument();
    });
});

describe('CreateScheduledMessageDialog — form validation', () => {
    it('shows validation errors when submitting empty form', async () => {
        renderDialog();
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: /^agendar$/i }));

        await waitFor(() => {
            expect(screen.getByText(/informe o id do contato/i)).toBeInTheDocument();
        });
    });

    it('shows validation error for empty contact name', async () => {
        renderDialog();
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: /^agendar$/i }));

        await waitFor(() => {
            expect(screen.getByText(/informe o nome do contato/i)).toBeInTheDocument();
        });
    });

    it('shows validation error for empty message', async () => {
        renderDialog();
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: /^agendar$/i }));

        await waitFor(() => {
            expect(screen.getByText(/informe a mensagem/i)).toBeInTheDocument();
        });
    });
});

describe('CreateScheduledMessageDialog — form submission', () => {
    it('calls POST /scheduled-messages with correct payload on valid submit', async () => {
        mockedApi.post.mockResolvedValueOnce({
            data: { message: 'Message scheduled successfully.', scheduled_message: mockMessage },
        });
        mockedApi.get.mockResolvedValueOnce({ data: { data: [], meta: {}, links: {} } });

        const { onOpenChange } = renderDialog();
        const user = userEvent.setup();

        await waitFor(() => {
            expect(screen.getByRole('combobox')).toBeInTheDocument();
        });

        await user.click(screen.getByRole('combobox'));
        await waitFor(() => {
            expect(screen.getByRole('option', { name: /huggy/i })).toBeInTheDocument();
        });
        await user.click(screen.getByRole('option', { name: /huggy/i }));

        await user.type(screen.getByLabelText(/id do contato/i), 'c-1');
        await user.type(screen.getByLabelText(/nome do contato/i), 'Alice Smith');
        await user.type(screen.getByLabelText(/mensagem/i), 'Hello!');
        await user.type(screen.getByLabelText(/data e hora/i), '2026-03-01T10:00');

        await user.click(screen.getByRole('button', { name: /^agendar$/i }));

        await waitFor(() => {
            expect(mockedApi.post).toHaveBeenCalledWith(
                '/scheduled-messages',
                expect.objectContaining({
                    contact_id: 'c-1',
                    contact_name: 'Alice Smith',
                    message: 'Hello!',
                }),
            );
        });
    });

    it('shows success toast and closes dialog on success', async () => {
        mockedApi.post.mockResolvedValueOnce({
            data: { message: 'OK', scheduled_message: mockMessage },
        });
        mockedApi.get.mockResolvedValueOnce({ data: { data: [], meta: {}, links: {} } });

        const { onOpenChange } = renderDialog();
        const user = userEvent.setup();

        await waitFor(() => expect(screen.getByRole('combobox')).toBeInTheDocument());
        await user.click(screen.getByRole('combobox'));
        await waitFor(() => expect(screen.getByRole('option', { name: /huggy/i })).toBeInTheDocument());
        await user.click(screen.getByRole('option', { name: /huggy/i }));

        await user.type(screen.getByLabelText(/id do contato/i), 'c-1');
        await user.type(screen.getByLabelText(/nome do contato/i), 'Alice');
        await user.type(screen.getByLabelText(/mensagem/i), 'Hi!');
        await user.type(screen.getByLabelText(/data e hora/i), '2026-03-01T10:00');

        await user.click(screen.getByRole('button', { name: /^agendar$/i }));

        await waitFor(() => {
            expect(mockedToast.success).toHaveBeenCalledWith('Mensagem agendada com sucesso.');
            expect(onOpenChange).toHaveBeenCalledWith(false);
        });
    });

    it('shows root error when API returns an error message', async () => {
        mockedApi.post.mockRejectedValueOnce({
            response: { data: { message: 'A data deve ser no futuro.' } },
        });

        renderDialog();
        const user = userEvent.setup();

        await waitFor(() => expect(screen.getByRole('combobox')).toBeInTheDocument());
        await user.click(screen.getByRole('combobox'));
        await waitFor(() => expect(screen.getByRole('option', { name: /huggy/i })).toBeInTheDocument());
        await user.click(screen.getByRole('option', { name: /huggy/i }));

        await user.type(screen.getByLabelText(/id do contato/i), 'c-1');
        await user.type(screen.getByLabelText(/nome do contato/i), 'Alice');
        await user.type(screen.getByLabelText(/mensagem/i), 'Hi!');
        await user.type(screen.getByLabelText(/data e hora/i), '2026-03-01T10:00');

        await user.click(screen.getByRole('button', { name: /^agendar$/i }));

        await waitFor(() => {
            expect(screen.getByText('A data deve ser no futuro.')).toBeInTheDocument();
        });
    });
});

describe('CreateScheduledMessageDialog — close behaviour', () => {
    it('closes dialog when cancel button is clicked', async () => {
        const { onOpenChange } = renderDialog();
        const user = userEvent.setup();

        await user.click(screen.getByRole('button', { name: /^cancelar$/i }));
        expect(onOpenChange).toHaveBeenCalledWith(false);
    });
});
