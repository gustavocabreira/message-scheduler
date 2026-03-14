import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ScheduledMessageCard } from '@/components/scheduled-messages/ScheduledMessageCard';
import { renderWithProviders } from '../../helpers/renderWithProviders';
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

function makeMessage(overrides: Partial<ScheduledMessage> = {}): ScheduledMessage {
    return {
        id: 1,
        contact_id: 'c-1',
        contact_name: 'Alice Smith',
        message: 'Hello, Alice!',
        scheduled_at: '2026-03-01T10:00:00Z',
        status: 'pending',
        attempts: 0,
        provider_connection_id: 1,
        created_at: '2026-02-18T21:00:00Z',
        updated_at: '2026-02-18T21:00:00Z',
        provider_connection: { id: 1, provider_type: 'huggy', provider_label: 'Huggy' },
        ...overrides,
    };
}

beforeEach(() => {
    jest.clearAllMocks();
});

describe('ScheduledMessageCard — rendering', () => {
    it('renders the contact name', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage()} />);
        expect(screen.getByText('Alice Smith')).toBeInTheDocument();
    });

    it('renders the message content', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage()} />);
        expect(screen.getByText('Hello, Alice!')).toBeInTheDocument();
    });

    it('renders the provider label', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage()} />);
        expect(screen.getByText(/huggy/i)).toBeInTheDocument();
    });

    it('renders "Pendente" badge for pending messages', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage({ status: 'pending' })} />);
        expect(screen.getByText('Pendente')).toBeInTheDocument();
    });

    it('renders "Enviado" badge for sent messages', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage({ status: 'sent' })} />);
        expect(screen.getByText('Enviado')).toBeInTheDocument();
    });

    it('renders "Falhou" badge for failed messages', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage({ status: 'failed' })} />);
        expect(screen.getByText('Falhou')).toBeInTheDocument();
    });

    it('renders "Cancelado" badge for cancelled messages', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage({ status: 'cancelled' })} />);
        expect(screen.getByText('Cancelado')).toBeInTheDocument();
    });

    it('shows attempt count when attempts > 0', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage({ attempts: 2 })} />);
        expect(screen.getByText(/2 tentativas/i)).toBeInTheDocument();
    });

    it('does not show attempt count when attempts is 0', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage({ attempts: 0 })} />);
        expect(screen.queryByText(/tentativa/i)).not.toBeInTheDocument();
    });

    it('shows "Provider desconhecido" when provider_connection is null', () => {
        renderWithProviders(
            <ScheduledMessageCard message={makeMessage({ provider_connection: null })} />,
        );
        expect(screen.getByText(/provider desconhecido/i)).toBeInTheDocument();
    });
});

describe('ScheduledMessageCard — cancel button', () => {
    it('shows cancel button for pending messages', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage({ status: 'pending' })} />);
        expect(
            screen.getByRole('button', { name: /cancelar agendamento/i }),
        ).toBeInTheDocument();
    });

    it('does not show cancel button for sent messages', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage({ status: 'sent' })} />);
        expect(
            screen.queryByRole('button', { name: /cancelar agendamento/i }),
        ).not.toBeInTheDocument();
    });

    it('does not show cancel button for cancelled messages', () => {
        renderWithProviders(
            <ScheduledMessageCard message={makeMessage({ status: 'cancelled' })} />,
        );
        expect(
            screen.queryByRole('button', { name: /cancelar agendamento/i }),
        ).not.toBeInTheDocument();
    });

    it('opens confirmation dialog when cancel button is clicked', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ScheduledMessageCard message={makeMessage()} />);

        await user.click(screen.getByRole('button', { name: /cancelar agendamento/i }));

        expect(screen.getByRole('alertdialog')).toBeInTheDocument();
        expect(screen.getByText(/tem certeza que deseja cancelar/i)).toBeInTheDocument();
    });

    it('calls DELETE API and shows success toast on confirm', async () => {
        mockedApi.delete.mockResolvedValueOnce({
            data: { message: 'Scheduled message cancelled successfully.' },
        });
        mockedApi.get.mockResolvedValueOnce({ data: { data: [], meta: {}, links: {} } });

        const user = userEvent.setup();
        renderWithProviders(<ScheduledMessageCard message={makeMessage()} />);

        await user.click(screen.getByRole('button', { name: /cancelar agendamento/i }));
        await user.click(
            screen.getByRole('button', { name: /cancelar agendamento/i, hidden: false }),
        );

        await waitFor(() => {
            expect(mockedApi.delete).toHaveBeenCalledWith('/scheduled-messages/1');
        });

        await waitFor(() => {
            expect(mockedToast.success).toHaveBeenCalledWith(
                expect.stringContaining('Alice Smith'),
            );
        });
    });

    it('shows error toast when cancellation fails', async () => {
        mockedApi.delete.mockRejectedValueOnce(new Error('Server error'));

        const user = userEvent.setup();
        renderWithProviders(<ScheduledMessageCard message={makeMessage()} />);

        await user.click(screen.getByRole('button', { name: /cancelar agendamento/i }));
        const confirmBtn = screen.getAllByRole('button', { name: /cancelar agendamento/i }).at(-1)!;
        await user.click(confirmBtn);

        await waitFor(() => {
            expect(mockedToast.error).toHaveBeenCalledWith(
                expect.stringContaining('Erro ao cancelar'),
            );
        });
    });
});

describe('ScheduledMessageCard — logs button', () => {
    it('shows "Ver logs" button for sent messages', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage({ status: 'sent' })} />);
        expect(screen.getByRole('button', { name: /ver logs/i })).toBeInTheDocument();
    });

    it('shows "Ver logs" button for failed messages', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage({ status: 'failed' })} />);
        expect(screen.getByRole('button', { name: /ver logs/i })).toBeInTheDocument();
    });

    it('does not show "Ver logs" button for pending messages', () => {
        renderWithProviders(<ScheduledMessageCard message={makeMessage({ status: 'pending' })} />);
        expect(screen.queryByRole('button', { name: /ver logs/i })).not.toBeInTheDocument();
    });

    it('opens logs dialog when "Ver logs" is clicked', async () => {
        mockedApi.get.mockResolvedValueOnce({ data: { data: [] } });

        const user = userEvent.setup();
        renderWithProviders(<ScheduledMessageCard message={makeMessage({ status: 'sent' })} />);

        await user.click(screen.getByRole('button', { name: /ver logs/i }));

        expect(screen.getByRole('dialog')).toBeInTheDocument();
        expect(screen.getByText(/logs de entrega/i)).toBeInTheDocument();
    });
});
