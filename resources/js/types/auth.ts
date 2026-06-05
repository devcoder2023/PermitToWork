export type Role =
    | 'system_admin'
    | 'execution_engineer'
    | 'site_manager'
    | 'permit_officer'
    | 'work_supervisor'
    | 'hse_officer'
    | 'consultant'
    | 'qa_inspector';

export type User = {
    id: number;
    name: string;
    email: string;
    role: Role;
    project_id?: number | null;
    sub_contractor_id?: number | null;
    phone?: string | null;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User | null;
};
