# Git Concepts

## How to use Git Patch

In this scenario, a Git Repo has been exported, and the contents of the repo deployed onto an environment.

This deployment does not have any knowledge of Git.

### 

### On the deployment:

Initialise a new git repo, so any changes can be tracked.

```text
git init
git add .
git commit -am "initial commit"
```

You can now make your changes, then we commit them as usual. Let's say we make 2 commits for this patch.

```text
echo 'hello world' > test-file
git add test-file
git commit -m "test commit"

echo 'some test' > another-test-file
git add another-test-file
git commit -m "added another file"
```

Then we create the patches for these 2 commits

```text
git format-patch -2
```

This will create 2 patch files.

### 

### On the Original Git Repo

Copy the patch files onto the machine with your actual git repo, then apply them like this

```text
git apply 0001-test-commit.patch
git diff # review changes
git add test-file
git commit -m "applied patch commit 1"

git apply 0002-added-another-file.patch
git diff # review changes
git add another-test-file
git commit -m "applied patch commit 2"
```

