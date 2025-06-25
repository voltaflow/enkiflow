import React from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';
import { FileText, Clock, CheckCircle, Lock, Send } from 'lucide-react';

interface User {
    id: number;
    name: string;
    email: string;
}

interface ApprovalBannerProps {
    isSubmitted: boolean;
    isApproved: boolean;
    isLocked: boolean;
    submittedAt: Date | string | null;
    approvedBy: User | null;
    onSubmit?: () => void;
}

export function ApprovalBanner({
    isSubmitted,
    isApproved,
    isLocked,
    submittedAt,
    approvedBy,
    onSubmit
}: ApprovalBannerProps) {
    const formatDate = (date: Date | string | null) => {
        if (!date) return '';
        const dateObj = typeof date === 'string' ? new Date(date) : date;
        return formatDistanceToNow(dateObj, { addSuffix: true, locale: es });
    };

    // Locked state - highest priority
    if (isLocked) {
        return (
            <Alert className="banner banner-locked border-red-200 bg-red-50">
                <Lock className="h-4 w-4 text-red-600" />
                <AlertDescription className="flex items-center justify-between">
                    <span className="text-red-900">
                        Esta hoja de tiempo está bloqueada y no puede ser modificada
                    </span>
                </AlertDescription>
            </Alert>
        );
    }

    // Approved state
    if (isApproved) {
        return (
            <Alert className="banner banner-approved border-green-200 bg-green-50">
                <CheckCircle className="h-4 w-4 text-green-600" />
                <AlertDescription className="flex items-center justify-between">
                    <span className="text-green-900">
                        Aprobado por {approvedBy?.name || 'un administrador'} 
                        {submittedAt && ` ${formatDate(submittedAt)}`}
                    </span>
                </AlertDescription>
            </Alert>
        );
    }

    // Submitted state
    if (isSubmitted) {
        return (
            <Alert className="banner banner-submitted border-blue-200 bg-blue-50">
                <Clock className="h-4 w-4 text-blue-600" />
                <AlertDescription className="flex items-center justify-between">
                    <span className="text-blue-900">
                        Enviado para revisión 
                        {submittedAt && ` ${formatDate(submittedAt)}`} - 
                        Esperando aprobación
                    </span>
                </AlertDescription>
            </Alert>
        );
    }

    // Draft state (default)
    return (
        <Alert className="banner banner-draft border-yellow-200 bg-yellow-50">
            <FileText className="h-4 w-4 text-yellow-600" />
            <AlertDescription className="flex items-center justify-between">
                <span className="text-yellow-900">
                    Borrador - Esta hoja de tiempo no ha sido enviada para aprobación
                </span>
                {onSubmit && (
                    <Button 
                        size="sm"
                        onClick={onSubmit}
                        className="ml-4"
                    >
                        <Send className="h-3 w-3 mr-2" />
                        Enviar para aprobación
                    </Button>
                )}
            </AlertDescription>
        </Alert>
    );
}