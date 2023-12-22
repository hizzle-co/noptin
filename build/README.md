This folder is built using the source code in the noptin-source repo. Only people with access to the source repo can contribute.

## Contributing.

>>> **NOTE**: On Windows, you can use Git Bash to run the following as it supports Unix-like commands.

- [ ] Navigate to the .git/hooks directory in the noptin repository.
- [ ] Create a new file named pre-commit (without any extension).
- [ ] Open the pre-commit file in VS Code and add the following script:

```shell
#!/bin/sh
cd ../noptin-source && npm run build
```

- [ ] Save the file and close the text editor.
- [ ] Make the pre-commit file executable by running the following command in Git Bash:

```shell
chmod +x .git/hooks/pre-commit
```
