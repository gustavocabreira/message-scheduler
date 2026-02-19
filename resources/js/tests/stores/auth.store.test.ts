import { useAuthStore } from '@/stores/auth.store';
import type { AuthUser } from '@/stores/auth.store';

const mockUser: AuthUser = { id: 1, name: 'Ada Lovelace', email: 'ada@example.com' };

function getStore() {
    return useAuthStore.getState();
}

beforeEach(() => {
    useAuthStore.setState({ user: null, isAuthenticated: false });
});

describe('auth.store — initial state', () => {
    it('starts unauthenticated with no user', () => {
        const { user, isAuthenticated } = getStore();
        expect(user).toBeNull();
        expect(isAuthenticated).toBe(false);
    });
});

describe('auth.store — setUser()', () => {
    it('sets user and marks as authenticated', () => {
        getStore().setUser(mockUser);
        const { user, isAuthenticated } = getStore();
        expect(user).toEqual(mockUser);
        expect(isAuthenticated).toBe(true);
    });

    it('setting user to null marks as unauthenticated', () => {
        getStore().setUser(mockUser);
        getStore().setUser(null);
        const { user, isAuthenticated } = getStore();
        expect(user).toBeNull();
        expect(isAuthenticated).toBe(false);
    });

    it('replaces an existing user', () => {
        const anotherUser: AuthUser = { id: 2, name: 'Grace Hopper', email: 'grace@example.com' };
        getStore().setUser(mockUser);
        getStore().setUser(anotherUser);
        expect(getStore().user).toEqual(anotherUser);
    });
});

describe('auth.store — logout()', () => {
    it('clears user and sets isAuthenticated to false', () => {
        getStore().setUser(mockUser);
        getStore().logout();
        const { user, isAuthenticated } = getStore();
        expect(user).toBeNull();
        expect(isAuthenticated).toBe(false);
    });

    it('is idempotent when called on an already logged-out store', () => {
        getStore().logout();
        getStore().logout();
        expect(getStore().isAuthenticated).toBe(false);
        expect(getStore().user).toBeNull();
    });
});
