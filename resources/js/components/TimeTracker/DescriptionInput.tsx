import React, { ChangeEvent } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface DescriptionInputProps {
    value: string;
    placeholder?: string;
    disabled: boolean;
    onChange: (value: string) => void;
    onBlur?: () => void;
}

export function DescriptionInput({
    value,
    placeholder = '¿En qué estás trabajando?',
    disabled,
    onChange,
    onBlur
}: DescriptionInputProps) {
    const handleChange = (e: ChangeEvent<HTMLInputElement>) => {
        onChange(e.target.value);
    };

    return (
        <div className="space-y-2">
            <Label htmlFor="description-input">Descripción</Label>
            <Input
                id="description-input"
                type="text"
                value={value}
                onChange={handleChange}
                onBlur={onBlur}
                placeholder={placeholder}
                disabled={disabled}
                className="w-full"
                autoComplete="off"
            />
        </div>
    );
}