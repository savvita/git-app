let prevSha = [];
let currentSha = '';
let branchSha = '';

$(document).ready(async () => {
    await loadBranch();

    $('#branch').on('change', async (e) => {
        await loadBranch();
    });

    formatContent();
});

const loadBranch = async () => {
    const sha = $('#branch').val();
    if(!sha) {
        return;
    }
    prevSha.splice(0, prevSha.length);
    branchSha = sha;

    let href = $('#downloadRepo').attr('href');
    if(href) {
        href = href.substring(0, href.lastIndexOf('/') + 1);
        href += sha;
        $('#downloadRepo').attr('href', href);
    }

    const msg = $('#branch option:selected').attr('data-last-commit-message');
    const date = (new Date($('#branch option:selected').attr('data-last-commit-date'))).toLocaleString();

    $('#last_commit').text(`Last commit: ${msg} at ${date}`);

    await loadTree(sha);
}

const loadTree = async (sha) => {
    const username = $('#branch').attr('data-username');
    const repoName = $('#branch').attr('data-repo');

    await $.ajax({
        url: `/repository/tree/${username}/${repoName}/${sha}`,
        type: 'GET',
        success: function(data) {
            if(data && data.tree) {
                currentSha = sha;
                showTree(data.tree);
            }
        }
    });
}

const sortTree = (a, b) => {
    if (a['type'] < b['type']){
        return 1;
    }
    if (a['type'] > b['type']){
        return -1;
    }
    return 0;
}

const showTree = (tree) => {
    tree.sort(sortTree);
    const cont = $('#tree_table');
    cont.empty();

    const file_icon = `<svg aria-label="File" aria-hidden="true" height="16" viewBox="0 0 16 16" version="1.1" width="16" data-view-component="true" >
    <path
        d="M2 1.75C2 .784 2.784 0 3.75 0h6.586c.464 0 .909.184 1.237.513l2.914 2.914c.329.328.513.773.513 1.237v9.586A1.75 1.75 0 0 1 13.25 16h-9.5A1.75 1.75 0 0 1 2 14.25Zm1.75-.25a.25.25 0 0 0-.25.25v12.5c0 .138.112.25.25.25h9.5a.25.25 0 0 0 .25-.25V6h-2.75A1.75 1.75 0 0 1 9 4.25V1.5Zm6.75.062V4.25c0 .138.112.25.25.25h2.688l-.011-.013-2.914-2.914-.013-.011Z"></path>
    </svg>`;

    const dir_icon = `<svg aria-label="Directory" aria-hidden="true" height="16" viewBox="0 0 16 16" version="1.1" width="16" data-view-component="true">
        <path fill="#eeeeee" stroke="#000000" d="M1.75 1A1.75 1.75 0 0 0 0 2.75v10.5C0 14.216.784 15 1.75 15h12.5A1.75 1.75 0 0 0 16 13.25v-8.5A1.75 1.75 0 0 0 14.25 3H7.5a.25.25 0 0 1-.2-.1l-.9-1.2C6.07 1.26 5.55 1 5 1H1.75Z"></path>
    </svg>`;

    if(prevSha.length > 0) {
        cont.append(
            `<tr>
                <td>${dir_icon}</td>
                <td class="cursor-pointer" onClick="goBack()">..</td>
                <td></td>
                <td></td>
             </tr>`);
    }

    const username = $('#branch').attr('data-username');
    const repoName = $('#branch').attr('data-repo');

    for (let node of tree) {
        const dirTd = `<td class="cursor-pointer" data-sha="${node['sha']}" onClick="directoryClick(event)">${node['path']}</td>`;

        const blobTd = `<td class="cursor-pointer"><a class="text-decoration-none text-dark" href="/repository/blob/${username}/${repoName}/${branchSha}/${node['path']}/${node['sha']}">${node['path']}</a></td>`;
        cont.append(
            `<tr>
                <td>
                    ${node['type'] === 'blob' ? file_icon : dir_icon}
                </td>
                ${ node['type'] === 'tree' ? dirTd : blobTd }
                <td class="text-center">${ node['type'] === 'blob' ? (node['size'] / 1024).toFixed(2) : '' }</td>
             </tr>`);
    }
}

const directoryClick = async (e) => {
    const sha = e.target.getAttribute('data-sha');
    if(currentSha !== '') {
        prevSha.push(currentSha);
    }
    await loadTree(sha);
}

const goBack = async () => {
    if(prevSha.length === 0) {
        return;
    }
    const sha = prevSha.pop();
    await loadTree(sha);
}

const formatContent = () => {
    const pre = document.getElementsByTagName('pre');

    if(!pre) {
        return;
    }

    const pre_count = pre.length;
    for (let i = 0; i < pre_count; i++) {
        pre[i].innerHTML = '<span class="line-number"></span>' + pre[i].innerHTML + '<span class="cl"></span>';

        let lines = pre[i].innerHTML.split(/\n/);

        let count = lines.length;

        let patch = getPatch(pre[i]);
        if(patch == null) {
            patch = {
                added: {
                    min: 0,
                    max: 0
                },
                values: []
            }
        }

        let del = 0;

        for (let j = patch.added.min; j <= patch.added.max; j++) {
            let className = null;
            let deletedText = null;

            if(patch.values[0].startsWith('+')) {
                lines[j - del] = `<span class="line-added">${lines[j - del]}</span>`;
            } else if(patch.values[0].startsWith('-')) {
                className = "line-deleted";
                deletedText = patch.values[0].substring(1);
                lines[j] = `${lines[j]} <span class="line-deleted">${deletedText}</span>`;
                del++;
            }
            patch.values = patch.values.slice(1);

            if(patch.values.length == 0) {
                break;
            }
        }

        pre[i].innerHTML = '<span class="line-number"></span>' + lines.join('\n') + '<span class="cl"></span>';

        count = lines.length;

        for (let j = 0; j < count; j++) {
            let line_num = pre[i].getElementsByTagName('span')[0];
            line_num.innerHTML += `<span>` + (j + 1) + '</span>';
        }
    }
}

const getPatch = (element) => {
    const patch = element.getAttribute('data-patch');

    if(!patch) {
        return null;
    }
    let changes = patch.split('@@');
    const idx = changes[1].substring(changes[1].indexOf('+') + 1).split(',');

    const str = changes[2].split('\n').filter(element => element.trim());

    return {
        added: {
            min: parseInt(idx[0]),
            max: parseInt(idx[0]) + parseInt(idx[1])
        },
        values: str
    };
}
