export * from './auth';

export type DiseaseSummary = {
    id: number;
    name: string;
    description: string;
    image_path: string | null;
    treatments_count: number;
};

export interface Treatment {
    id: number;
    title: string;
    description: string;
    type: string;
}

export interface Disease {
    id: number;
    name: string;
    description: string;
    causes: string | null;
    symptoms: string | null;
    history: string | null;
    sources: string | null;
    prevention_tips: string | null;
    image_path: string | null;
    treatments: Treatment[];
}
