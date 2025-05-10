import * as React from "react";

interface HeadingProps {
    title?: string;
    description?: string;
    children?: React.ReactNode;
}

function Heading({ title, description, children }: HeadingProps) {
    const headingText = title || (typeof children === 'string' ? children : '');

    return (
        <div className="mb-8 space-y-0.5">
            <h2 className="text-xl font-semibold tracking-tight">{headingText}</h2>
            {description && <p className="text-muted-foreground text-sm">{description}</p>}
        </div>
    );
}

export { Heading };
export default Heading;
