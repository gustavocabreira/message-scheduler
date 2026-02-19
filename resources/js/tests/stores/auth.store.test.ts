import { useAuthStore } from '@/stores/auth.store';
import type { AuthUser } from '@/stores/auth.store';

const mockUser: AuthUser = { id: 1, name: 'Ada Lovelace', email: 'ada@example.com' };
const mockToken = 'test-sanctum-token-abc123';

function getStore() {
    return useAuthStore.getState();
}

beforeEach(() => {
    useAuthStore.setState({ user: null, token: null, isAuthenticated: false });
});

describe('auth.store — initial state', () => {
    it('starts unauthenticated with no user or token', () => {
        const { user, token, isAuthenticated } = getStore();
        expect(user).toBeNull();
        expect(token).toBeNull();
        expect(isAuthenticated).toBe(false);
    });
});

describe('auth.store — setToken()', () => {
    it('stores the token and marks as authenticated', () => {
        getStore().setToken(mockToken);
        const { token, isAuthenticated } = getStore();
        expect(token).toBe(mockToken);
        expect(isAuthenticated).toBe(true);
    });

    it('replaces an existing token', () => {
        getStore().setToken(mockToken);
        getStore().setToken('new-token-xyz');
        expect(getStore().token).toBe('new-token-xyz');
    });

    it('does not clear the user when updating the token', () => {
        getStore().setUser(mockUser);
        getStore().setToken(mockToken);
        expect(getStore().user).toEqual(mockUser);
    });
});

describe('auth.store — setUser()', () => {
    it('sets user without changing isAuthenticated', () => {
        getStore().setToken(mockToken);
        getStore().setUser(mockUser);
        const { user, isAuthenticated } = getStore();
        expect(user).toEqual(mockUser);
        expect(isAuthenticated).toBe(true);
    });

    it('replaces an existing user', () => {
        const anotherUser: AuthUser = { id: 2, name: 'Grace Hopper', email: 'grace@example.com' };
        getStore().setUser(mockUser);
        getStore().setUser(anotherUser);
        expect(getStore().user).toEqual(anotherUser);
    });
});

describe('auth.store — logout()', () => {
    it('clears user, token, and sets isAuthenticated to false', () => {
        getStore().setToken(mockToken);
        getStore().setUser(mockUser);
        getStore().logout();
        const { user, token, isAuthenticated } = getStore();
        expect(user).toBeNull();
        expect(token).toBeNull();
        expect(isAuthenticated).toBe(false);
    });

    it('is idempotent when called on an already logged-out store', () => {
        getStore().logout();
        getStore().logout();
        expect(getStore().isAuthenticated).toBe(false);
        expect(getStore().user).toBeNull();
        expect(getStore().token).toBeNull();
    });
});
