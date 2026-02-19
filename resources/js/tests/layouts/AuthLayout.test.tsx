import { screen } from '@testing-library/react';
import { render } from '@testing-library/react';
import { MemoryRouter, Route, Routes } from 'react-router-dom';
import { AuthLayout } from '@/layouts/AuthLayout';

function renderAuthLayout(initialEntry = '/login', outlet = <p>Child content</p>) {
    return render(
        <MemoryRouter initialEntries={[initialEntry]}>
            <Routes>
                <Route element={<AuthLayout />}>
                    <Route path="/login" element={outlet} />
                    <Route path="/register" element={outlet} />
                </Route>
            </Routes>
        </MemoryRouter>,
    );
}

describe('AuthLayout', () => {
    it('renders the branding text in the left panel', () => {
        renderAuthLayout();
        const brandInstances = screen.getAllByText('Message Scheduler');
        expect(brandInstances.length).toBeGreaterThanOrEqual(1);
    });

    it('renders the marketing quote', () => {
        renderAuthLayout();
        expect(screen.getByText(/agende mensagens com precisão/i)).toBeInTheDocument();
    });

    it('renders the outlet (child content)', () => {
        renderAuthLayout('/login', <p>Login form here</p>);
        expect(screen.getByText('Login form here')).toBeInTheDocument();
    });

    it('renders a different outlet on /register', () => {
        renderAuthLayout('/register', <p>Register form here</p>);
        expect(screen.getByText('Register form here')).toBeInTheDocument();
    });
});
