export interface User {
    id: number;
    name: string;
    email: string;
    huggy_id: string | null;
    role: "admin" | "operator" | null;
    avatar_path: string | null;
    avatar_url: string | null;
    created_at: string;
}
