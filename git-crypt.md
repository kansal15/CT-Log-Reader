---
description: git way to encrypt files within git repo
---

# git-crypt

## git-crypt - transparent file encryption in git

git-crypt enables transparent encryption and decryption of files in a git repository. Files which you choose to protect are encrypted when committed, and decrypted when checked out. git-crypt lets you freely share a repository containing a mix of public and private content. git-crypt gracefully degrades, so developers without the secret key can still clone and commit to a repository with encrypted files. This lets you store your secret material \(such as keys or passwords\) in the same repository as your code, without requiring you to lock down your entire repository.

git-crypt was written by [Andrew Ayer](https://www.agwa.name) \([agwa@andrewayer.name](mailto:agwa@andrewayer.name)\). For more information, see [https://www.agwa.name/projects/git-crypt](https://www.agwa.name/projects/git-crypt).

### 

### Building git-crypt

See the [INSTALL.md](https://github.com/AGWA/git-crypt/blob/master/INSTALL.md) file.

### 

### Using git-crypt

Configure a repository to use git-crypt:

```text
cd repo
git-crypt init
```

Specify files to encrypt by creating a .gitattributes file:

```text
secretfile filter=git-crypt diff=git-crypt
*.key filter=git-crypt diff=git-crypt
secretdir/** filter=git-crypt diff=git-crypt
```

Like a .gitignore file, it can match wildcards and should be checked into the repository. See below for more information about .gitattributes. Make sure you don't accidentally encrypt the .gitattributes file itself \(or other git files like .gitignore or .gitmodules\). Make sure your .gitattributes rules are in place _before_ you add sensitive files, or those files won't be encrypted!

Share the repository with others \(or with yourself\) using GPG:

```text
git-crypt add-gpg-user USER_ID
```

`USER_ID` can be a key ID, a full fingerprint, an email address, or anything else that uniquely identifies a public key to GPG \(see "HOW TO SPECIFY A USER ID" in the gpg man page\). Note: `git-crypt add-gpg-user` will add and commit a GPG-encrypted key file in the .git-crypt directory of the root of your repository.

Alternatively, you can export a symmetric secret key, which you must securely convey to collaborators \(GPG is not required, and no files are added to your repository\):

```text
git-crypt export-key /path/to/key
```

After cloning a repository with encrypted files, unlock with GPG:

```text
git-crypt unlock
```

Or with a symmetric key:

```text
git-crypt unlock /path/to/key
```

That's all you need to do - after git-crypt is set up \(either with `git-crypt init` or `git-crypt unlock`\), you can use git normally - encryption and decryption happen transparently.

