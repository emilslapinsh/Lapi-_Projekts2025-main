/** @type {import('prettier').Config} */
export default {
    plugins: ['prettier-plugin-blade'],
    tabWidth: 4,
    printWidth: 120,
    semi: true,
    singleQuote: true,
    trailingComma: 'es5',
    bracketSameLine: false,
    overrides: [
        {
            files: ['*.blade.php'],
            options: {
                parser: 'blade',
                tabWidth: 4,
                printWidth: 120,
            },
        },
    ],
};
