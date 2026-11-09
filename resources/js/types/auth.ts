export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    role: 'admin' | 'operator' | 'keuangan';
    roles?: Array<{ id: number; name: string }>;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

/** Extract the user's primary role for compatibility with Spatie HasRoles. */
export const getUserRole = (user: Auth['user']): string => {
    if (typeof user.role === 'string') return user.role;
    if (Array.isArray(user.roles) && user.roles.length > 0) return user.roles[0].name;
    return 'operator';
};

export type Auth = {
    user: User;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
