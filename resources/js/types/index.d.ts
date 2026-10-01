export interface User {
    id: number;
    name: string;
    email: string;
    role: 'farmer' | 'admin' | 'lgu_staff';
    email_verified_at?: string;
}

export interface PageProps {
    auth: {
        user: User;
    };
}
