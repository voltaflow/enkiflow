import { usePage } from '@inertiajs/react';

interface FeatureFlags {
    project_permissions?: boolean;
    project_permissions_ui?: boolean;
    bulk_permissions?: boolean;
    permission_templates?: boolean;
    permission_audit?: boolean;
    temporary_permissions?: boolean;
    [key: string]: boolean | undefined;
}

export function useFeature(featureName: string): boolean {
    const page = usePage();

    // Get features from page props
    const features = (page.props.features || {}) as FeatureFlags;

    // Check if feature is enabled
    return features[featureName] === true;
}

export function useFeatures(): FeatureFlags {
    const page = usePage();
    return (page.props.features || {}) as FeatureFlags;
}
